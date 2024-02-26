<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

use froq\file\{File, Image};
use froq\file\upload\{FileSource, ImageSource, SourceError};

/**
 * Payload class for working with an uploaded file.
 *
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\FilePayload
 * @author  Kerem Güneş
 * @since   7.3
 */
class FilePayload extends Payload implements PayloadInterface
{
    /** Is file state. */
    public readonly bool $okay;

    /** Uploaded file instance. */
    public readonly UploadedFile|null $file;

    /**
     * @override
     */
    public function __construct($request)
    {
        parent::__construct($request);

        $this->okay = $this->isTypeOkay('multipart/form-data');

        if ($this->okay) {
            // Take first one, single file only.
            $file = first($this->request->files());
        }

        $this->file = $this->createUploadedFile($file ?? []);
    }

    /**
     * @see UploadedFile.__get()
     */
    public function __get(string $field): mixed
    {
        return $this->file->offsetGet($field);
    }

    /**
     * @see UploadedFile.__set()
     */
    public function __set(string $field, mixed $_): never
    {
        $this->file->offsetSet($field, $_);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_select($this->toArray(), $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function getAll(array $keys = null, array $defaults = null): array
    {
        return array_select($this->toArray(), $keys, $defaults);
    }

    /**
     * @see UploadedFile.generateId()
     */
    public function generateId(string $type = 'uuid', mixed ...$options): string|null
    {
        return $this->file->generateId($type, ...$options);
    }

    /**
     * @see UploadedFile.generateHash()
     */
    public function generateHash(string $algo = 'md5'): string|null
    {
        return $this->file->generateHash($algo);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return isset($this->file) ? $this->file->toArray() : [];
    }

    /**
     * @see UploadedFile.exists()
     */
    public function exists(): bool
    {
        return $this->file->exists();
    }

    /**
     * @see UploadedFile.move()
     */
    public function move(string $to, array $options = null, int $mode = File::MODE): string|null
    {
        return $this->file->move($to, $options, $mode);
    }

    /**
     * @see UploadedFile.remove()
     */
    public function remove(): bool|null
    {
        return $this->file->remove();
    }

    /**
     * @see UploadedFile.open()
     */
    public function open(): File
    {
        return $this->file->open();
    }

    /**
     * @see UploadedFile.openImage()
     */
    public function openImage(): Image
    {
        return $this->file->openImage();
    }

    /**
     * @see UploadedFile.openSource()
     */
    public function openSource(array $options = null): Source
    {
        return $this->file->openSource($options);
    }

    /**
     * @see UploadedFile.openImageSource()
     */
    public function openImageSource(array $options = null): ImageSource
    {
        return $this->file->openImageSource($options);
    }

    /**
     * Create an uploaded file.
     */
    private function createUploadedFile(array $file): UploadedFile
    {
        $props = array_default([], UploadedFile::FIELDS);

        if ($this->validateFields($file)) {
            if ($error = $file['error']) {
                $props['error'] = new \Error(SourceError::toMessage($error), $error);
            } else {
                $props['mime'] = file_mime($file['tmp_name']);

                foreach ($file as $field => $value) {
                    match ($field) {
                        '_id'       => $props['id']   = (string) $value,
                        'name'      => $props['name'] = (string) $value,
                        'type'      => $props['type'] = (string) $value,
                        'size'      => $props['size'] = (int)    $value,
                        'full_path' => $props['path'] = (string) $value,
                        'tmp_name'  => $props['temp'] = (string) $value,
                        default     => null // Pass.
                    };
                };
            }
        }

        return new UploadedFile(...$props);
    }

    /**
     * Verify file fields.
     */
    private function validateFields(array $file): bool
    {
        $fields = $this->generateFields();
        $fields = array_include($file, $fields);

        foreach ($fields as $value) {
            if ($value === null) {
                return false;
            }
        }

        $temp = $file['tmp_name'] ?? null;

        return $temp && is_uploaded_file($temp);
    }

    /**
     * Generate file fields as expected in $_FILES.
     */
    private function generateFields(): array
    {
        static $fields; // @see http://php.net/manual/en/features.file-upload.post-method.php
        return $fields ??= explode(',', 'name,type,size,full_path,tmp_name,error');
    }
}
