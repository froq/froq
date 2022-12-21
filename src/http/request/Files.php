<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

/**
 * A static class, for getting posted files.
 *
 * @package froq\http\request
 * @class   froq\http\request\Files
 * @author  Kerem Güneş
 * @since   1.0
 * @static
 */
class Files extends \StaticClass
{
    /**
     * Get normalizing all files.
     *
     * @return array
     * @since  4.0
     */
    public static function all(): array
    {
        return self::normalizeFiles($_FILES);
    }

    /**
     * Normalize files (two-dims only).
     *
     * @param  array $files
     * @return array
     */
    public static function normalizeFiles(array $files): array
    {
        $ret = [];

        foreach ($files as $id => $file) {
            if (!isset($file['name'])) {
                continue;
            }
            if (!is_array($file['name'])) {
                $ret[] = $file + ['_id' => $id]; // Add input name.
                continue;
            }

            foreach ($file['name'] as $i => $name) {
                $ret[] = [
                    'name'     => $name,
                    'type'     => $file['type'][$i],
                    'tmp_name' => $file['tmp_name'][$i],
                    'error'    => $file['error'][$i],
                    'size'     => $file['size'][$i],
                ] + ['_id' => $id .'['. $i .']']; // Add input name.
            }
        }

        return $ret;
    }
}
