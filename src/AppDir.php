<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

/**
 * A static class, provides app related dirs.
 *
 * @package froq
 * @class   froq\AppDir
 * @author  Kerem Güneş
 * @since   7.0
 */
class AppDir
{
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
     * @return array<string>
     */
    public static function getAutoloadDirs(bool $assoc = false): array
    {
        if (file_exists($json = APP_DIR . '/composer.json')) {
            $data = json_decode(file_get_contents($json), true);
            $dirs = (array) ($data['autoload']['psr-4'] ?? []);

            if ($dirs) {
                $dirs = array_map_keys(
                    fn($ns) => rtrim((string) $ns, '\\'),
                    $dirs
                );

                if (!$assoc) {
                    $dirs = array_values($dirs);
                }
            }

            return $dirs;
        }

        return [];
    }
}
