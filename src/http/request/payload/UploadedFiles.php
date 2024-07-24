<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

use froq\common\interface\Arrayable;
use froq\file\{Path, PathInfo};

/**
 * A list class for uploaded files.
 *
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\UploadedFiles
 * @author  Kerem Güneş
 * @since   7.3
 */
class UploadedFiles extends \ItemList
{
    /**
     * @override
     */
    public function __construct(array $files)
    {
        foreach ($files as &$file) {
            if (!$file instanceof UploadedFile) {
                $file = UploadedFile::from($file);
            }
        }

        parent::__construct($files);
    }

    /**
     * @override
     */
    public function toArray(bool $deep = false): array
    {
        $files = parent::toArray();

        if ($deep) foreach ($files as $i => $file) {
            $files[$i] = $file->toArray();
        }

        return $files;
    }
}
