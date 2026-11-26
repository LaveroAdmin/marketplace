<?php

namespace LaveroAdmin\Admin\Marketplace\Services;

use Illuminate\Contracts\Foundation\Application;

class LocalExtensionLoader
{
    protected static array $registeredDirs = [];

    public static function storagePath(?string $key = null): string
    {
        $base = storage_path('app/lavero-extensions');

        return $key !== null && $key !== '' ? $base . DIRECTORY_SEPARATOR . $key : $base;
    }

    public static function isLocallyInstalled(string $key): bool
    {
        $dir = static::storagePath($key);

        return is_dir($dir) && (file_exists($dir . '/composer.json') || file_exists($dir . '/extension.json'));
    }

    public static function getManifest(string $key): ?array
    {
        $file = static::storagePath($key) . '/extension.json';
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode((string) @file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    public static function registerAll(Application $app): void
    {
        $base = static::storagePath();
        if (!is_dir($base)) {
            return;
        }

        $dirs = glob($base . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($dirs as $dir) {
            static::registerExtension($app, $dir);
        }
    }

    public static function registerExtension(Application $app, string $dir): void
    {
        $realDir = realpath($dir) ?: $dir;
        if (isset(static::$registeredDirs[$realDir])) {
            return;
        }

        $composerFile = $realDir . '/composer.json';
        if (!file_exists($composerFile)) {
            return;
        }

        $composer = json_decode((string) @file_get_contents($composerFile), true);
        if (!is_array($composer)) {
            return;
        }

        static::$registeredDirs[$realDir] = true;

        $psr4 = (array) ($composer['autoload']['psr-4'] ?? []);
        if (!empty($psr4)) {
            spl_autoload_register(static function (string $class) use ($psr4, $realDir): void {
                foreach ($psr4 as $prefix => $relativePath) {
                    if (!str_starts_with($class, $prefix)) {
                        continue;
                    }

                    $relativeClass = substr($class, strlen($prefix));
                    $subPath = trim((string) $relativePath, '/\\');
                    $file = $realDir
                        . ($subPath !== '' ? DIRECTORY_SEPARATOR . $subPath : '')
                        . DIRECTORY_SEPARATOR
                        . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                        . '.php';

                    if (file_exists($file)) {
                        require_once $file;
                        return;
                    }
                }
            });
        }

        $files = (array) ($composer['autoload']['files'] ?? []);
        foreach ($files as $relFile) {
            $full = $realDir . DIRECTORY_SEPARATOR . ltrim((string) $relFile, '/\\');
            if (file_exists($full)) {
                require_once $full;
            }
        }

        $providers = (array) ($composer['extra']['laravel']['providers'] ?? []);
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $app->register($provider);
            }
        }
    }
}
