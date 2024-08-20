<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

use froq\file\{Path, PathInfo, FileSystem};
use const APP_DIR;

/**
 * A static / readonly class, provides app related dirs.
 *
 * Note: This class depends on APP_DIR constant defined in `pub/index.php` file.
 *
 * @package froq
 * @class   froq\AppDir
 * @author  Kerem Güneş
 * @since   7.0
 */
class AppDir
{
    /** App directory name. */
    public readonly string $name;

    /**
     * @constructor
     */
    public function __construct()
    {
        $this->name = APP_DIR;
    }

    /**
     * @magic
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get path.
     *
     * @return froq\file\Path
     */
    public function getPath(): Path
    {
        return new Path($this->name);
    }

    /**
     * Get path info.
     *
     * @return froq\file\PathInfo
     */
    public function getPathInfo(): PathInfo
    {
        return new PathInfo($this->name);
    }

    /**
     * Get dir.
     *
     * @return string
     */
    public static function getDir(): string
    {
        return APP_DIR;
    }

    /**
     * Get config dir.
     *
     * @return string
     */
    public static function getConfigDir(): string
    {
        return APP_DIR . '/app/config';
    }

    /**
     * Get library dir.
     *
     * @return string
     */
    public static function getLibraryDir(): string
    {
        return APP_DIR . '/app/library';
    }

    /**
     * Get service dir.
     *
     * @return string
     */
    public static function getServiceDir(): string
    {
        return APP_DIR . '/app/service';
    }

    /**
     * Get system dir.
     *
     * @return string
     */
    public static function getSystemDir(): string
    {
        return APP_DIR . '/app/system';
    }

    /**
     * Get bin dir.
     *
     * @return string
     */
    public static function getBinDir(): string
    {
        return APP_DIR . '/bin';
    }

    /**
     * Get pub dir.
     *
     * @return string
     */
    public static function getPubDir(): string
    {
        return APP_DIR . '/pub';
    }

    /**
     * Get var dir.
     *
     * @return string
     */
    public static function getVarDir(): string
    {
        return APP_DIR . '/var';
    }

    /**
     * Get vendor dir.
     *
     * @return string
     */
    public static function getVendorDir(): string
    {
        return APP_DIR . '/vendor';
    }

    /**
     * Get autoload dir.
     *
     * @param  bool $assoc
     * @return array<string>
     */
    public static function getAutoloadDirs(bool $assoc = false): array
    {
        if (file_exists($file = APP_DIR . '/composer.json')) {
            $data = json_decode(file_get_contents($file), true);
            $dirs = $data['autoload']['psr-4'] ?? [];

            if ($dirs) {
                $dirs = array_map_keys(
                    fn($ns) => rtrim((string) $ns, '\\'),
                    (array) $dirs
                );

                if (!$assoc) {
                    $dirs = array_values($dirs);
                }
            }
        }

        return $dirs ?? [];
    }

    /**
     * Make a path string based on APP_DIR, with formatter form or path parts.
     *
     * Examples:
     * ```
     * // For "APP_DIR/app/system/Index/view/home.php" file.
     * $contName = $controller->getShortName();
     * $viewName = 'home';
     *
     * $path = AppDir::toPath('app/system/%s/view/%s.php', $contName, $viewName);
     * $path = AppDir::toPath('app/system/', $contName, '/view/', $viewName . '.php');
     * ```
     *
     * @param  string    $base
     * @param  string ...$parts
     * @return string
     */
    public static function toPath(string $base, string ...$parts): string
    {
        if (str_contains($base, '%')) {
            $path = sprintf($base, ...$parts);
        } else {
            $path = FileSystem::joinPath($base, ...$parts);
        }

        return APP_DIR . FileSystem::normalizePath($path);
    }

    /**
     * Include a file.
     *
     * @param  string $file
     * @param  bool   $once
     * @return mixed
     * @throws froq\AppError
     */
    public static function include(string $file, bool $once = false): mixed
    {
        $path = FileSystem::resolvePath(APP_DIR . '/' . $file)
            ?: throw new AppError('No file exists: %q', $file);

        return $once ? include_once $path : include $path;
    }

    /**
     * Require a file.
     *
     * @param  string $file
     * @param  bool   $once
     * @return mixed
     * @throws froq\AppError
     */
    public static function require(string $file, bool $once = false): mixed
    {
        $path = FileSystem::resolvePath(APP_DIR . '/' . $file)
            ?: throw new AppError('No file exists: %q', $file);

        return $once ? require_once $path : require $path;
    }
}
