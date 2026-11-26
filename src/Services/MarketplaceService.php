<?php

namespace LaveroAdmin\Admin\Marketplace\Services;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaveroAdmin\Admin\Admin;
use Symfony\Component\Process\Process;

class MarketplaceService
{
    protected CompatibilityChecker $compatibilityChecker;

    public function __construct(?CompatibilityChecker $checker = null)
    {
        $this->compatibilityChecker = $checker ?: new CompatibilityChecker();
    }

    /**
     * Get configured repositories list.
     *
     * @return array
     */
    public function getRepositories(): array
    {
        $repos = config('admin.extensions.marketplace.repositories', null);
        if (is_null($repos)) {
            $repos = config('admin.marketplace.repositories', null);
        }

        if (!is_array($repos) || empty($repos)) {
            $repos = [
                'official' => [
                    'name'      => 'Official Repository',
                    'type'      => 'remote',
                    'url'       => 'https://raw.githubusercontent.com/LaveroAdmin/marketplace/master/repository.json',
                    'cache_ttl' => 3600,
                ],
                'local' => [
                    'name' => 'Local Development',
                    'type' => 'local',
                    'path' => base_path('../../packages/lavero-admin/extensions'),
                ],
            ];
        }

        return $repos;
    }

    /**
     * Clear repository caches and refresh extensions list.
     *
     * @return array
     */
    public function refreshRepositories(): array
    {
        $repos = $this->getRepositories();
        foreach ($repos as $key => $cfg) {
            $cacheKey = 'lavero_marketplace_repo_' . md5($key . json_encode($cfg));
            Cache::forget($cacheKey);
        }

        return $this->getExtensionsList(true);
    }

    /**
     * Get all extensions aggregated from all repositories with compatibility and installation info.
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getExtensionsList(bool $forceRefresh = false): array
    {
        $repos = $this->getRepositories();
        $aggregated = [];

        foreach ($repos as $repoKey => $repoConfig) {
            $packages = $this->fetchRepositoryPackages($repoKey, $repoConfig, $forceRefresh);
            foreach ($packages as $pkg) {
                $key = $pkg['key'] ?? Str::afterLast(Str::afterLast($pkg['name'] ?? '', '/'), 'lavero-admin-');
                if (empty($key)) {
                    continue;
                }

                // If already seen, later repositories (e.g. local) can override or enrich earlier ones
                if (isset($aggregated[$key])) {
                    $aggregated[$key] = array_replace_recursive($aggregated[$key], $pkg);
                } else {
                    $aggregated[$key] = $pkg;
                }
                $aggregated[$key]['repository'] = $repoKey;
                $aggregated[$key]['repository_name'] = $repoConfig['name'] ?? ucfirst($repoKey);
            }
        }

        // If no packages found from repositories (e.g. offline and no local path), load bundled repository
        if (empty($aggregated)) {
            $bundled = $this->loadBundledRepository();
            foreach ($bundled as $pkg) {
                $key = $pkg['key'] ?? Str::afterLast(Str::afterLast($pkg['name'] ?? '', '/'), 'lavero-admin-');
                $pkg['repository'] = 'bundled';
                $pkg['repository_name'] = 'Bundled Offline Repository';
                $aggregated[$key] = $pkg;
            }
        }

        $items = [];
        $locale = app()->getLocale();

        foreach ($aggregated as $key => $meta) {
            $packageName = $meta['name'] ?? '';
            $remoteVersion = (string) ($meta['version'] ?? '1.0.0');

            // 1. Try to load local or vendor extension.json manifest for latest local info
            $localManifest = $this->loadManifest($key, $packageName);
            if (!empty($localManifest)) {
                $meta = array_replace_recursive($meta, $localManifest);
            }

            // 2. Multilingual localization
            $meta = $this->localizeManifest($meta, $locale);

            // 3. Installed status & installed version
            $isInstalled = false;
            $version = $meta['version'] ?? $remoteVersion;
            $installedViaZip = LocalExtensionLoader::isLocallyInstalled($key);

            if ($installedViaZip) {
                $isInstalled = true;
                $zipManifest = LocalExtensionLoader::getManifest($key);
                if (!empty($zipManifest['version'])) {
                    $version = (string) $zipManifest['version'];
                }
            } elseif (class_exists(InstalledVersions::class)) {
                if (!empty($packageName) && InstalledVersions::isInstalled($packageName)) {
                    $isInstalled = true;
                    $version = InstalledVersions::getPrettyVersion($packageName) ?: $version;
                } elseif (InstalledVersions::isInstalled("laveroadmin/{$key}")) {
                    $isInstalled = true;
                    $version = InstalledVersions::getPrettyVersion("laveroadmin/{$key}") ?: $version;
                }
            }

            $cleanInstalled = ltrim((string) $version, 'vV');
            $cleanRemote = ltrim($remoteVersion, 'vV');
            $hasUpdate = $isInstalled
                && preg_match('/^\d+\.\d+/', $cleanInstalled)
                && preg_match('/^\d+\.\d+/', $cleanRemote)
                && version_compare($cleanRemote, $cleanInstalled, '>');

            // 4. Enabled status in admin
            $isEnabled = array_key_exists($key, Admin::$extensions)
                || array_key_exists(basename(str_replace('-', '_', $key)), Admin::$extensions)
                || (config("admin.extensions.{$key}.enable") !== false && $isInstalled);

            // 5. Compatibility check
            $compatCheck = $this->compatibilityChecker->check($meta['compatibility'] ?? [], $locale);

            $items[$key] = array_merge($meta, [
                'key'            => $key,
                'installed'      => $isInstalled,
                'version'        => $version,
                'latest_version' => $remoteVersion,
                'enabled'        => $isEnabled,
                'compatibility'  => $compatCheck,
                'has_update'     => $hasUpdate,
                'install_driver' => $installedViaZip ? 'zip' : ($this->canUseComposer() ? 'composer' : 'zip'),
            ]);
        }

        return $items;
    }

    /**
     * Fetch packages from a single repository definition.
     *
     * @param string $repoKey
     * @param array $config
     * @param bool $forceRefresh
     * @return array
     */
    protected function fetchRepositoryPackages(string $repoKey, array $config, bool $forceRefresh = false): array
    {
        $type = $config['type'] ?? 'remote';
        $cacheKey = 'lavero_marketplace_repo_' . md5($repoKey . json_encode($config));
        $cacheTtl = (int) ($config['cache_ttl'] ?? 3600);

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $packages = [];

        try {
            if ($type === 'local') {
                $path = $config['path'] ?? '';
                if ($path && is_dir($path)) {
                    $dirs = glob($path . '/*', GLOB_ONLYDIR) ?: [];
                    foreach ($dirs as $dir) {
                        $manifestFile = $dir . '/extension.json';
                        if (file_exists($manifestFile)) {
                            $data = json_decode((string) @file_get_contents($manifestFile), true);
                            if (is_array($data)) {
                                $data['key'] = $data['key'] ?? basename($dir);
                                $packages[] = $data;
                            }
                        }
                    }
                }
            } elseif ($type === 'remote') {
                $url = $config['url'] ?? '';
                if ($url) {
                    $response = Http::timeout(5)->get($url);
                    if ($response->successful()) {
                        $json = $response->json();
                        $packages = $json['packages'] ?? (is_array($json) ? $json : []);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log or ignore network error; will fall back cleanly
        }

        if (!empty($packages)) {
            Cache::put($cacheKey, $packages, $cacheTtl);
        }

        return $packages;
    }

    /**
     * Load bundled repository.json shipped with marketplace extension.
     *
     * @return array
     */
    protected function loadBundledRepository(): array
    {
        $bundledPath = __DIR__ . '/../../repository.json';
        if (file_exists($bundledPath)) {
            $content = (string) @file_get_contents($bundledPath);
            $json = json_decode($content, true);
            if (isset($json['packages']) && is_array($json['packages'])) {
                return $json['packages'];
            }
        }

        return [];
    }

    /**
     * Localize fields with multilingual fallback.
     *
     * @param array $data
     * @param string|null $locale
     * @return array
     */
    protected function localizeManifest(array $data, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();

        if (isset($data['title'])) {
            $data['title'] = $this->resolveLocaleValue($data['title'], $locale);
        }

        if (isset($data['description'])) {
            $data['description'] = $this->resolveLocaleValue($data['description'], $locale);
        }

        if (isset($data['setup']) && is_array($data['setup'])) {
            if (isset($data['setup']['requirements'])) {
                $data['setup']['requirements'] = $this->resolveLocaleValue($data['setup']['requirements'], $locale);
            }
            if (isset($data['setup']['notes'])) {
                $data['setup']['notes'] = $this->resolveLocaleValue($data['setup']['notes'], $locale);
            }
            if (isset($data['setup']['commands']) && is_array($data['setup']['commands'])) {
                foreach ($data['setup']['commands'] as &$cmd) {
                    if (is_array($cmd) && isset($cmd['desc'])) {
                        $cmd['desc'] = $this->resolveLocaleValue($cmd['desc'], $locale);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Resolve localized value from array, falling back to 'en' or first value.
     *
     * @param mixed $value
     * @param string $locale
     * @return mixed
     */
    protected function resolveLocaleValue($value, string $locale)
    {
        if (is_array($value)) {
            if (isset($value[$locale])) {
                return $value[$locale];
            }
            if (isset($value['en'])) {
                return $value['en'];
            }
            if (!empty($value) && !array_is_list($value)) {
                return reset($value);
            }
        }

        return $value;
    }

    /**
     * Search candidate paths for an extension manifest.
     *
     * @param string $key
     * @param string $packageName
     * @return array|null
     */
    protected function loadManifest(string $key, string $packageName): ?array
    {
        $candidatePaths = [
            LocalExtensionLoader::storagePath($key) . '/extension.json',
            base_path("vendor/{$packageName}/extension.json"),
            base_path("../../packages/lavero-admin/extensions/{$key}/extension.json"),
            dirname(__DIR__, 4) . "/extensions/{$key}/extension.json",
            __DIR__ . "/../../../{$key}/extension.json",
        ];

        foreach ($candidatePaths as $path) {
            if (file_exists($path)) {
                $content = @file_get_contents($path);
                if ($content) {
                    $json = json_decode($content, true);
                    if (is_array($json)) {
                        return $json;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Run extension lifecycle action.
     *
     * @param string $action
     * @param string $key
     * @return array
     */
    public function runAction(string $action, string $key): array
    {
        $extensions = $this->getExtensionsList();

        if (!isset($extensions[$key])) {
            return ['status' => false, 'message' => "Unknown extension: {$key}", 'output' => ''];
        }

        $meta = $extensions[$key];
        $packageName = $meta['name'];
        $baseDir = base_path();
        $installedViaZip = LocalExtensionLoader::isLocallyInstalled($key);
        $useComposer = $this->canUseComposer() && !$installedViaZip;

        $output = '';

        try {
            switch ($action) {
                case 'install':
                    if (!$useComposer) {
                        return $this->installOrUpdateViaZip($key, $meta, 'install');
                    }

                    $cmd = array_merge($this->findComposerCommand(), ['require', $packageName]);
                    $res = $this->executeCommand($cmd, $baseDir);
                    $output .= $res['output'] . PHP_EOL;

                    if ($res['exit_code'] === 0) {
                        Artisan::call('migrate', ['--force' => true]);
                        $output .= PHP_EOL . '--- Artisan migrate ---' . PHP_EOL . Artisan::output();
                        Artisan::call('admin:import', ['extension' => $key]);
                        $output .= PHP_EOL . "--- Artisan admin:import {$key} ---" . PHP_EOL . Artisan::output();
                        return ['status' => true, 'message' => "Extension {$packageName} installed successfully.", 'output' => $output];
                    }
                    return ['status' => false, 'message' => "Composer require failed with code {$res['exit_code']}", 'output' => $output];

                case 'update':
                    if (!$useComposer) {
                        return $this->installOrUpdateViaZip($key, $meta, 'update');
                    }

                    $cmd = array_merge($this->findComposerCommand(), ['update', $packageName]);
                    $res = $this->executeCommand($cmd, $baseDir);
                    $output .= $res['output'] . PHP_EOL;

                    if ($res['exit_code'] === 0) {
                        Artisan::call('migrate', ['--force' => true]);
                        $output .= PHP_EOL . '--- Artisan migrate ---' . PHP_EOL . Artisan::output();
                        Artisan::call('admin:import', ['extension' => $key]);
                        $output .= PHP_EOL . "--- Artisan admin:import {$key} ---" . PHP_EOL . Artisan::output();
                        return ['status' => true, 'message' => "Extension {$packageName} updated successfully.", 'output' => $output];
                    }
                    return ['status' => false, 'message' => "Composer update failed with code {$res['exit_code']}", 'output' => $output];

                case 'import':
                    Artisan::call('migrate', ['--force' => true]);
                    $output .= '--- Artisan migrate ---' . PHP_EOL . Artisan::output();
                    Artisan::call('admin:import', ['extension' => $key]);
                    $output .= PHP_EOL . Artisan::output();
                    return ['status' => true, 'message' => "Assets & settings imported for {$key}.", 'output' => $output];

                case 'remove':
                    if ($installedViaZip) {
                        $targetDir = LocalExtensionLoader::storagePath($key);
                        $this->deleteDirectory($targetDir);
                        return ['status' => true, 'message' => "Extension {$packageName} removed from local storage.", 'output' => "Removed directory: {$targetDir}"];
                    }

                    if (!$this->canUseComposer()) {
                        return [
                            'status'  => false,
                            'message' => 'Composer CLI / proc_open is unavailable on this hosting. Remove the package via composer.json.',
                            'output'  => '',
                        ];
                    }

                    $cmd = array_merge($this->findComposerCommand(), ['remove', $packageName]);
                    $res = $this->executeCommand($cmd, $baseDir);
                    $output .= $res['output'];

                    if ($res['exit_code'] === 0) {
                        return ['status' => true, 'message' => "Extension {$packageName} removed.", 'output' => $output];
                    }
                    return ['status' => false, 'message' => "Composer remove failed with code {$res['exit_code']}", 'output' => $output];

                default:
                    return ['status' => false, 'message' => "Unknown action: {$action}", 'output' => ''];
            }
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'output' => $e->getTraceAsString()];
        }
    }

    /**
     * Download and install/update an extension from its ZIP archive (shared-hosting friendly, no Composer/proc_open).
     */
    public function installOrUpdateViaZip(string $key, array $meta, string $action = 'install'): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [
                'status'  => false,
                'message' => 'PHP ZipArchive extension is required for ZIP installation.',
                'output'  => '',
            ];
        }

        $urls = $this->resolveZipUrls($meta);
        if (empty($urls)) {
            return [
                'status'  => false,
                'message' => "No download URL or GitHub repository found for {$key}.",
                'output'  => '',
            ];
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'lavero_ext_');
        if ($tmpZip === false) {
            return ['status' => false, 'message' => 'Failed to create temporary file.', 'output' => ''];
        }

        $downloadedFrom = null;
        $log = "Shared-hosting ZIP mode ({$action})\n";

        try {
            foreach ($urls as $url) {
                $log .= "Downloading: {$url}\n";
                $response = Http::timeout(60)->withOptions(['sink' => $tmpZip])->get($url);
                if ($response->successful() && filesize($tmpZip) > 0) {
                    $downloadedFrom = $url;
                    break;
                }
            }

            if ($downloadedFrom === null) {
                @unlink($tmpZip);
                return [
                    'status'  => false,
                    'message' => 'Failed to download extension archive.',
                    'output'  => $log,
                ];
            }

            $result = $this->installFromZipFile($tmpZip, $key);
            $result['output'] = $log . ($result['output'] ?? '');

            return $result;
        } finally {
            if (file_exists($tmpZip)) {
                @unlink($tmpZip);
            }
        }
    }

    /**
     * Extract an extension ZIP archive into storage/app/lavero-extensions/{key},
     * register its autoloader/provider, and run migrations + admin:import.
     */
    public function installFromZipFile(string $zipPath, ?string $expectedKey = null): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return ['status' => false, 'message' => 'PHP ZipArchive extension is not enabled.', 'output' => ''];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['status' => false, 'message' => 'Could not open ZIP archive.', 'output' => ''];
        }

        $tmpExtract = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lavero_ext_extract_' . uniqid();
        @mkdir($tmpExtract, 0755, true);

        try {
            if (!$zip->extractTo($tmpExtract)) {
                $zip->close();
                return ['status' => false, 'message' => 'Failed to extract ZIP archive.', 'output' => ''];
            }
            $zip->close();

            // Detect single top-level directory (e.g. GitHub Repo-master/)
            $entries = array_values(array_diff(scandir($tmpExtract) ?: [], ['.', '..']));
            $sourceDir = $tmpExtract;
            if (count($entries) === 1 && is_dir($tmpExtract . DIRECTORY_SEPARATOR . $entries[0])) {
                $sourceDir = $tmpExtract . DIRECTORY_SEPARATOR . $entries[0];
            }

            $manifest = [];
            if (file_exists($sourceDir . '/extension.json')) {
                $manifest = json_decode((string) @file_get_contents($sourceDir . '/extension.json'), true) ?: [];
            }

            $composer = [];
            if (file_exists($sourceDir . '/composer.json')) {
                $composer = json_decode((string) @file_get_contents($sourceDir . '/composer.json'), true) ?: [];
            }

            $key = $expectedKey
                ?: ($manifest['key'] ?? null)
                ?: Str::afterLast(Str::afterLast((string) ($composer['name'] ?? ''), '/'), 'lavero-admin-');

            $key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key);
            if ($key === '') {
                return [
                    'status'  => false,
                    'message' => 'Invalid extension archive: missing extension.json or composer.json.',
                    'output'  => '',
                ];
            }

            $baseStorage = LocalExtensionLoader::storagePath();
            if (!is_dir($baseStorage)) {
                @mkdir($baseStorage, 0755, true);
            }

            $targetDir = LocalExtensionLoader::storagePath($key);
            if (is_dir($targetDir)) {
                $this->deleteDirectory($targetDir);
            }

            $this->copyDirectory($sourceDir, $targetDir);

            // Register autoloader and service provider immediately for Artisan commands
            LocalExtensionLoader::registerExtension(app(), $targetDir);

            $output = "Extracted to: {$targetDir}\n";

            try {
                Artisan::call('migrate', ['--force' => true]);
                $output .= "\n--- Artisan migrate ---\n" . Artisan::output();
            } catch (\Throwable $e) {
                $output .= "\n--- Artisan migrate warning: {$e->getMessage()} ---\n";
            }

            try {
                Artisan::call('admin:import', ['extension' => $key]);
                $output .= "\n--- Artisan admin:import {$key} ---\n" . Artisan::output();
            } catch (\Throwable $e) {
                $output .= "\n--- Artisan admin:import warning: {$e->getMessage()} ---\n";
            }

            $version = $manifest['version'] ?? '';

            return [
                'status'  => true,
                'message' => "Extension [{$key}]" . ($version ? " (v{$version})" : '') . ' installed/updated successfully.',
                'output'  => $output,
                'key'     => $key,
            ];
        } finally {
            $this->deleteDirectory($tmpExtract);
        }
    }

    protected function resolveZipUrls(array $meta): array
    {
        $urls = [];
        foreach (['zip_url', 'download_url'] as $field) {
            if (!empty($meta[$field]) && is_string($meta[$field])) {
                $urls[] = $meta[$field];
            }
        }

        if (!empty($meta['github']) && is_string($meta['github'])) {
            $github = rtrim($meta['github'], '/');
            $urls[] = "{$github}/archive/refs/heads/master.zip";
            $urls[] = "{$github}/archive/refs/heads/main.zip";
        }

        return array_values(array_unique($urls));
    }

    protected function canUseComposer(): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('proc_open', $disabled, true)) {
            return false;
        }

        if (file_exists(base_path('composer.phar'))) {
            return true;
        }

        try {
            $res = $this->executeCommand(['composer', '--version'], base_path());
            return $res['exit_code'] === 0;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function copyDirectory(string $src, string $dst): void
    {
        @mkdir($dst, 0755, true);
        $items = scandir($src) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $s = $src . DIRECTORY_SEPARATOR . $item;
            $d = $dst . DIRECTORY_SEPARATOR . $item;
            if (is_dir($s)) {
                $this->copyDirectory($s, $d);
            } else {
                @copy($s, $d);
            }
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Execute shell command.
     *
     * @param array $command
     * @param string $cwd
     * @return array
     */
    protected function executeCommand(array $command, string $cwd): array
    {
        $process = new Process($command, $cwd, null, null, 300);
        $process->run();

        return [
            'exit_code' => $process->getExitCode(),
            'output'    => $process->getOutput() . $process->getErrorOutput(),
        ];
    }

    /**
     * Find Composer command parts.
     *
     * @return array
     */
    protected function findComposerCommand(): array
    {
        if (file_exists(base_path('composer.phar'))) {
            return [PHP_BINARY ?: 'php', base_path('composer.phar')];
        }

        return ['composer'];
    }
}
