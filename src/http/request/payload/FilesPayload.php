<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

/**
 * Payload class for working with all uploaded files.
 *
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\FilesPayload
 * @author  Kerem Güneş
 * @since   7.3
 */
class FilesPayload extends Payload implements PayloadInterface, \Countable
{
    /** Is file state. */
    public readonly bool $okay;

    /** Uploaded file lists.
     * @var array<froq\http\request\payload\FilePayload>
     */
    public readonly array $files;

    /**
     * @override
     */
    public function __construct($request)
    {
        parent::__construct($request);

        $this->okay = $this->isTypeOkay('multipart/form-data');

        if ($this->okay) {
            // Take all uploaded file.
            $files = $this->request->files();
        }

        $this->files = $this->createFilePayloadList($files ?? []);
    }

    /**
     * @inheritDoc
     */
    public function get(string|int $key, mixed $default = null): mixed
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
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Create a list from file payloads.
     */
    private function createFilePayloadList(array $files): array
    {
        $ref = reflect(FilePayload::class);

        foreach ($files as $i => $file) {
            // Without constructor.
            $filePayload = $ref->init();

            $ref->getParent()->getProperty('request')
                ->setValue($filePayload, $this->request);

            $file = $ref->getMethod('createUploadedFile')
                ->invoke($filePayload, $file);

            $ref->getProperty('okay')
                ->setValue($filePayload, true);
            $ref->getProperty('file')
                ->setValue($filePayload, $file);

            $files[$i] = $filePayload;
        }

        return $files;
    }
}
