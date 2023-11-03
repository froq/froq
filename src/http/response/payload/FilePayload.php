<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response\payload;

use froq\http\{Response, message\ContentType};
use froq\file\{File, FileException};

/**
 * Payload class for sending files as response content.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\FilePayload
 * @author  Kerem Güneş
 * @since   4.0
 */
class FilePayload extends Payload implements PayloadInterface
{
    /**
     * Constructor.
     *
     * @param int        $code
     * @param string     $content
     * @param array|null $attributes
     * @param froq\http\Response|null @internal
     */
    public function __construct(int $code, string $content, array $attributes = null, Response $response = null)
    {
        $attributes['type'] = ContentType::APPLICATION_OCTET_STREAM;

        parent::__construct($code, $content, $attributes, $response);
    }

    /**
     * @inheritDoc froq\http\response\payload\PayloadInterface
     */
    public function handle(): File
    {
        $file = $this->getContent();

        [$fileName, $fileMime, $fileExtension, $fileSize, $modifiedAt]
            = $this->getAttributes(['name', 'mime', 'extension', 'size', 'modifiedAt']);

        if (!$file) {
            throw new PayloadException('File empty');
        } elseif (!is_string($file)) {
            throw new PayloadException('File content must be a valid readable file path '.
                'or source file data, %t given', $file);
        } elseif (isset($fileName) && !$this->isValidFileName($fileName)) {
            throw new PayloadException('File name must be string and not contain non-ascii characters');
        }

        try {
            // A regular file.
            if (@is_file($file)) {
                $file = new File($file);
                $file->open('rb');
            } else {
                // Or contents of file.
                $file = File::fromString($file);
            }

            $fileMime && $file->setMime($fileMime);
            $fileExtension && $file->setExtension($fileExtension);
        } catch (FileException $e) {
            throw new PayloadException($e, cause: $e->getCause());
        }

        $fileName      = $fileName      ?: $file->getPath();
        $fileMime      = $fileMime      ?: $file->getMime();
        $fileExtension = $fileExtension ?: $file->getExtension();
        $fileSize      = $fileSize      ?: $file->size();
        $modifiedAt    = $modifiedAt    ?: $file->stat()['mtime'];

        if ($fileName) {
            $baseName = $fileName;
            $fileName = file_name($baseName);
            if (str_contains($baseName, '.')) {
                $fileExtension = file_extension($baseName);
            }
        }

        $fileName = $fileName . '.' . $fileExtension;
        $fileMime = $fileMime ?: ContentType::APPLICATION_OCTET_STREAM;

        // Update attributes.
        $this->setAttributes([
            'name'   => $fileName, 'mime'       => $fileMime,
            'size'   => $fileSize, 'modifiedAt' => $modifiedAt,
        ]);

        return $file;
    }

    /**
     * Valid file name checker.
     */
    private function isValidFileName(mixed $fileName): bool
    {
        return is_string($fileName) && preg_test('~^[\w\+\-\.]+$~', $fileName);
    }
}
