<?php

namespace LaveroAdmin\Admin\Marketplace\Services;

use LaveroAdmin\Admin\Admin;

class CompatibilityChecker
{
    /**
     * Check compatibility of an extension with current environment.
     *
     * @param array $compat
     * @param string|null $locale
     * @return array
     */
    public function check(array $compat = [], ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();

        $checks = [];
        $compatible = true;
        $reasons = [];

        // 1. PHP Version
        $reqPhp = $compat['php'] ?? '>=8.1';
        $curPhp = PHP_VERSION;
        $phpPassed = $this->satisfies($curPhp, $reqPhp);
        if (!$phpPassed) {
            $compatible = false;
            $reasons[] = trans('lavero-admin-marketplace::marketplace.compat_php_req', [
                'required' => $reqPhp,
                'current'  => $curPhp,
            ], $locale);
        }
        $checks[] = [
            'type'     => 'php',
            'title'    => trans('lavero-admin-marketplace::marketplace.compat_php_title', [], $locale),
            'required' => $reqPhp,
            'current'  => $curPhp,
            'passed'   => $phpPassed,
        ];

        // 2. Laravel Framework Version
        $reqLaravel = $compat['laravel'] ?? '^10.0 || ^11.0 || ^12.0 || ^13.0';
        $curLaravel = app()->version();
        $laravelPassed = $this->satisfies($curLaravel, $reqLaravel);
        if (!$laravelPassed) {
            $compatible = false;
            $reasons[] = trans('lavero-admin-marketplace::marketplace.compat_laravel_req', [
                'required' => $reqLaravel,
                'current'  => $curLaravel,
            ], $locale);
        }
        $checks[] = [
            'type'     => 'laravel',
            'title'    => trans('lavero-admin-marketplace::marketplace.compat_laravel_title', [], $locale),
            'required' => $reqLaravel,
            'current'  => $curLaravel,
            'passed'   => $laravelPassed,
        ];

        // 3. LaveroAdmin Version
        $reqAdmin = $compat['admin'] ?? '^1.0';
        $curAdmin = defined(Admin::class . '::VERSION') ? Admin::VERSION : '1.0.0';
        $adminPassed = $this->satisfies($curAdmin, $reqAdmin);
        if (!$adminPassed) {
            $compatible = false;
            $reasons[] = trans('lavero-admin-marketplace::marketplace.compat_admin_req', [
                'required' => $reqAdmin,
                'current'  => $curAdmin,
            ], $locale);
        }
        $checks[] = [
            'type'     => 'admin',
            'title'    => trans('lavero-admin-marketplace::marketplace.compat_admin_title', [], $locale),
            'required' => $reqAdmin,
            'current'  => $curAdmin,
            'passed'   => $adminPassed,
        ];

        // 4. Required PHP Modules
        $reqExts = $compat['extensions'] ?? [];
        if (!empty($reqExts) && is_array($reqExts)) {
            $missing = [];
            foreach ($reqExts as $ext) {
                if (!extension_loaded($ext)) {
                    $missing[] = $ext;
                }
            }
            $extsPassed = empty($missing);
            if (!$extsPassed) {
                $compatible = false;
                $reasons[] = trans('lavero-admin-marketplace::marketplace.compat_exts_req', [
                    'exts' => implode(', ', $missing),
                ], $locale);
            }
            $checks[] = [
                'type'     => 'extensions',
                'title'    => trans('lavero-admin-marketplace::marketplace.compat_exts_title', [], $locale),
                'required' => implode(', ', $reqExts),
                'current'  => $extsPassed
                    ? trans('lavero-admin-marketplace::marketplace.compat_all_loaded', [], $locale)
                    : trans('lavero-admin-marketplace::marketplace.compat_missing_exts', [
                        'exts' => implode(', ', $missing),
                    ], $locale),
                'passed'   => $extsPassed,
            ];
        }

        return [
            'compatible' => $compatible,
            'summary'    => $compatible
                ? trans('lavero-admin-marketplace::marketplace.compat_fully_compatible', [], $locale)
                : trans('lavero-admin-marketplace::marketplace.compat_incompatible', [], $locale),
            'reasons'    => $reasons,
            'checks'     => $checks,
        ];
    }

    /**
     * Check if a given version satisfies a composer-like version constraint.
     *
     * @param string $version
     * @param string $constraint
     * @return bool
     */
    public function satisfies(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*' || strtolower($constraint) === 'any') {
            return true;
        }

        // Clean version (e.g., "v1.0.27" -> "1.0.27", "11.2.0-dev" -> "11.2.0")
        $version = ltrim($version, 'vV');
        $version = preg_replace('/-[a-zA-Z0-9.]+$/', '', $version);

        // Split OR expressions (|| or |)
        $orBranches = preg_split('/\s*\|\|\s*|\s*\|\s*/', $constraint);
        foreach ($orBranches as $branch) {
            $branch = trim($branch);
            if ($branch === '') {
                continue;
            }

            if ($this->satisfiesBranch($version, $branch)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if version satisfies all AND clauses in a single branch.
     *
     * @param string $version
     * @param string $branch
     * @return bool
     */
    protected function satisfiesBranch(string $version, string $branch): bool
    {
        // Split AND conditions (space or comma)
        $tokens = preg_split('/(?<=\d|\w|\*)\s+(?=[<>=~^])|,\s*/', $branch);

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || $token === '*') {
                continue;
            }

            if (!$this->satisfiesSingle($version, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate single condition (e.g. ">=8.1", "^10.0", "~2.1", "<12.0").
     *
     * @param string $version
     * @param string $condition
     * @return bool
     */
    protected function satisfiesSingle(string $version, string $condition): bool
    {
        $condition = trim($condition);

        // Caret constraint: ^X.Y.Z
        if (str_starts_with($condition, '^')) {
            $minVer = substr($condition, 1);
            $parts = explode('.', $minVer);
            $major = (int) ($parts[0] ?? 0);
            $minor = (int) ($parts[1] ?? 0);

            if ($major > 0) {
                $maxVer = ($major + 1) . '.0.0';
            } elseif ($minor > 0) {
                $maxVer = '0.' . ($minor + 1) . '.0';
            } else {
                $patch = (int) ($parts[2] ?? 0);
                $maxVer = '0.0.' . ($patch + 1);
            }

            return version_compare($version, $minVer, '>=') && version_compare($version, $maxVer, '<');
        }

        // Tilde constraint: ~X.Y
        if (str_starts_with($condition, '~')) {
            $minVer = substr($condition, 1);
            $parts = explode('.', $minVer);
            if (count($parts) >= 2) {
                $major = (int) $parts[0];
                $minor = (int) $parts[1];
                $maxVer = $major . '.' . ($minor + 1) . '.0';
            } else {
                $major = (int) ($parts[0] ?? 0);
                $maxVer = ($major + 1) . '.0.0';
            }

            return version_compare($version, $minVer, '>=') && version_compare($version, $maxVer, '<');
        }

        // Comparison operators: >=, <=, !=, >, <, ==, =
        if (preg_match('/^(>=|<=|!=|>|<|==|=)(.+)$/', $condition, $matches)) {
            $op = $matches[1];
            if ($op === '=') {
                $op = '==';
            }
            $targetVer = trim($matches[2]);
            return version_compare($version, $targetVer, $op);
        }

        // Exact match
        return version_compare($version, $condition, '==');
    }
}
