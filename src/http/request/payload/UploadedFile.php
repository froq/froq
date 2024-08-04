<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

use froq\common\interface\Arrayable;
use froq\file\{File, Image, Path, PathInfo, FileException};
use froq\file\upload\{FileSource, ImageSource, SourceException};

/**
 * An uploaded file class.
 *
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\UploadedFile
 * @author  Kerem Güneş
 * @since   7.3
 */
class UploadedFile implements Arrayable, \ArrayAccess
{
    /** Fields. */
    public const FIELDS = [
        'id', 'name', 'type', 'mime',
        'size', 'path', 'temp', 'error'
    ];

    /**
     * @constructor
     */
    public function __construct(
        public readonly string|null $id,
        public readonly string|null $name,
        public readonly string|null $type,
        public readonly string|null $mime,
        public readonly int|null    $size,
        public readonly string|null $path,
        public readonly string|null $temp,
        public readonly UploadedFileError|null $error,
    )
    {}

    /**
     * Check existence.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->temp && is_file($this->temp);
    }

    /**
     * Move file.
     *
     * @param  string     $to
     * @param  array|null $options
     * @return string|null
     * @throws froq\http\request\payload\UploadedFileException
     */
    public function move(string $to, array $options = null, int $mode = File::MODE): string|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            $source = new FileSource($this->toSourceArray(), $options);
            return $source->moveUploadedFile($to, null, $mode);
        } catch (SourceException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Remove file.
     *
     * @return bool|null
     * @throws froq\http\request\payload\UploadedFileException
     */
    public function remove(): bool|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            $source = new FileSource($this->toSourceArray());
            return $source->removeUploadedFile();
        } catch (SourceException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Open as file.
     *
     * @return froq\file\File|null
     * @throws froq\http\request\payload\UploadedFileException
     */
    public function open(): File|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            $file = new File($this->temp);
            $file->setMime($this->mime);

            return $file->open();
        } catch (FileException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Open as image file.
     *
     * @return froq\file\Image|null
     * @throws froq\http\request\payload\UploadedFileException
     */
    public function openImage(): Image|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            $file = new Image($this->temp);
            $file->setMime($this->mime);

            return $file->open();
        } catch (FileException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Open as file source for manipulation.
     *
     * @return froq\file\upload\FileSource|null
     * @causes froq\http\request\payload\UploadedFileException
     */
    public function openSource(array $options = null): FileSource|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->open()->toFileSource($options);
        } catch (SourceException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Open as image source for manipulation.
     *
     * @return froq\file\upload\ImageSource|null
     * @causes froq\http\request\payload\UploadedFileException
     */
    public function openImageSource(array $options = null): ImageSource|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->open()->toImageSource($options);
        } catch (SourceException $e) {
            throw new UploadedFileException($e);
        }
    }

    /**
     * Get uploaded temp file's Path object.
     *
     * @return froq\file\Path|null
     */
    public function getPath(): Path|null
    {
        return $this->temp ? new Path($this->temp) : null;
    }

    /**
     * Get uploaded temp file's PathInfo object.
     *
     * @return froq\file\PathInfo|null
     */
    public function getPathInfo(): PathInfo|null
    {
        return $this->temp ? new PathInfo($this->temp) : null;
    }

    /**
     * Generate an id to use a file name.
     *
     * @param  string   $type
     * @param  mixed ...$options
     * @return string
     * @throws froq\http\request\payload\UploadedFileException
     */
    public function generateId(string $type = 'uuid', mixed ...$options): string
    {
        switch ($type) {
            case 'uuid':
                $options['time'] ??= true; // Unix time prefix. @default
                $default = reflect('uuid', 'function')->getParameterDefaults();
                $options = array_select($options, $default, combine: true);

                return uuid(...$options);
            case 'suid':
                $default = reflect('suid', 'function')->getParameterDefaults();
                $options = array_select($options, $default, combine: true);

                return suid(...$options);
            case 'uniq':
                $default = reflect('get_unique_id')->getParameterDefaults();
                $options = array_select($options, $default, combine: true);

                return get_unique_id(...$options);
            case 'rand':
                $default = reflect('get_random_id')->getParameterDefaults();
                $options = array_select($options, $default, combine: true);

                return get_random_id(...$options);
            default:
                throw new UploadedFileException(
                    'Invalid type %q, [valids: uuid, suid, uniq, rand]',
                    $type
                );
        }
    }

    /**
     * Generate a id using file contents if exists.
     *
     * @param  string $algo
     * @return string|null
     */
    public function generateHash(string $algo = 'md5'): string|null
    {
        if (!$this->exists()) {
            return null;
        }

        return hash_file($algo, $this->temp) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        foreach (self::FIELDS as $field) {
            $info[$field] = $this[$field];
        }

        if ($info['error']) {
            $info['error'] = [
                'code'    => $info['error']->getCode(),
                'message' => $info['error']->getMessage()
            ];
        }

        return $info;
    }

    /**
     * Get info for source methods.
     *
     * @return array
     */
    public function toSourceArray(): array
    {
        return [
            'name'  => $this->name, 'mime'     => $this->mime,
            'size'  => $this->size, 'tmp_name' => $this->temp,
            'error' => $this->error?->getCode(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $field): bool
    {
        return property_exists($this, $field);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $field): mixed
    {
        return property_exists($this, $field) ? $this->$field : false;
    }

    /**
     * @inheritDoc
     * @throws ReadonlyError
     */
    public function offsetSet(mixed $field, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @inheritDoc
     * @throws ReadonlyError
     */
    public function offsetUnset(mixed $field): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @internal
     */
    public static function from(array $file): UploadedFile
    {
        $ref = reflect(FilePayload::class);

        // Without constructor.
        $filePayload = $ref->init();

        return $ref->getMethod('createUploadedFile')
            ->invoke($filePayload, $file);
    }
}
