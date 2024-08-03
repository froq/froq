<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\common\interface\Arrayable;

/**
 * Resource class for JSON responses, provides some utilities like filtering and transforming
 * fields, also status code and indent options.
 *
 * @package froq\app\data
 * @class   froq\app\data\Resource
 * @author  Kerem Güneş
 * @since   7.0
 */
class Resource implements Arrayable, \Stringable, \JsonSerializable
{
    /** HTTP status. */
    protected int $status = 200;

    /** Main fields. */
    protected array|null $data, $meta, $error;

    /** Options. */
    protected array $options = ['indent' => false];

    /** @abstract */
    protected array $filters = [];

    /** @abstract */
    protected array $transforms = [];

    /**
     * Constructor.
     *
     * @param array|null $data
     * @param array|null $meta
     * @param array|null $error
     * @param int        $status
     * @param array      $options
     */
    public function __construct(
        ?array $data = [], ?array $meta = null,
        ?array $error = null, int $status = 200,
        array $options = []
    )
    {
        $this->data = $data; $this->meta = $meta;
        $this->error = $error; $this->status = $status;
        $this->options = [...$this->options, ...$options];
    }

    /**
     * Used by Response for string conversion (if it's not caught by Controller call()
     * or callCallable() method).
     *
     * @note This method must be overridden if subclass business differs.
     * @inheritDoc Stringable
     */
    public function __toString(): string
    {
        return json_serialize(
            $this->toArray(),
            indent: (int) $this->options['indent']
        );
    }

    /**
     * Used by JsonPayload for JSON serialization.
     *
     * @note This method must be overridden if subclass business differs.
     * @inheritDoc JsonSerializable
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * @note This method must be overridden if subclass business differs.
     * @inheritDoc Arrayable
     */
    public function toArray(): array
    {
        $data = $this->data;
        if ($data && $this->filters) {
            $data = $this->filterFields($data);
        }
        if ($data && $this->transforms) {
            $data = $this->transformFields($data);
        }

        return [
            'status' => $this->status,
            'data'   => $data,
            'meta'   => $this->meta,
            'error'  => $this->error
        ];
    }

    /**
     * Assign data field.
     *
     * @param  array|null $data
     * @return self
     */
    public function withData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Assign meta field.
     *
     * @param  array|null $meta
     * @return self
     */
    public function withMeta(?array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Assign error field.
     *
     * @param  array|null $error
     * @return self
     */
    public function withError(?array $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Assign status field.
     *
     * @param  int $status
     * @return self
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Assign an option.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Filter only those fields if given in subclass (eg: ["id", "title"]).
     *
     * @param  array $data
     * @return array
     */
    protected function filterFields(array $data): array
    {
        $ret = [];

        $keys = array_flip($this->filters);
        foreach ($data as $key => $value) {
            // Lists.
            if (is_array($value)) {
                $ret[$key] = $this->filterFields($value);
                continue;
            }

            if (isset($keys[$key])) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Transform all fields if given in subclass (eg: ["name" => "title"]).
     *
     * @param  array $data
     * @return array
     */
    protected function transformFields(array $data): array
    {
        $ret = [];

        foreach ($data as $key => $value) {
            // Lists.
            if (is_array($value)) {
                $ret[$key] = $this->transformFields($value);
                continue;
            }

            if (isset($this->transforms[$key])) {
                $ret[$this->transforms[$key]] = $value;
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }
}
