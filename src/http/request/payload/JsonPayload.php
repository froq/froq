<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

/**
 * Payload class for parsed request JSON content.
 *
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\JsonPayload
 * @author  Kerem Güneş
 * @since   7.3
 */
class JsonPayload extends Payload implements PayloadInterface, \Countable
{
    /** Is json state. */
    public readonly bool $okay;

    /**
     * @override
     */
    public function __construct($request)
    {
        parent::__construct($request);

        $this->okay = $this->isTypeOkay('~[/+]json~');
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null, mixed ...$options): mixed
    {
        return $this->okay ? $this->request->post($key, $default, ...$options) : $default;
    }

    /**
     * @inheritDoc
     */
    public function getAll(array $keys = null, array $defaults = null, mixed ...$options): array
    {
        return $this->okay ? $this->request->postParams($keys, $defaults, ...$options) : $defaults ?? [];
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->okay ? $_POST : [];
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->toArray());
    }
}
