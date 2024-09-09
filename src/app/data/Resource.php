<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\common\interface\{Arrayable, Jsonable};
use froq\http\response\{Status, payload\JsonPayload};

/**
 * Resource class for JSON responses, provides some utilities like filtering and transforming
 * fields, also status code and indent options.
 *
 * @package froq\app\data
 * @class   froq\app\data\Resource
 * @author  Kerem Güneş
 * @since   7.0
 */
class Resource implements Arrayable, Jsonable, \Stringable, \JsonSerializable
{
    /** HTTP status. */
    protected int $status;

    /** Data & meta fields. */
    protected array|null $data, $meta;

    /** Error field. */
    protected mixed $error;

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
     * @param mixed      $error
     * @param int        $status
     * @param array      $options
     */
    public function __construct(
        array|null $data = null, array|null $meta = null,
        mixed $error = null, int $status = Status::OKAY,
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
        return $this->toJson();
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
     * Used by serializer methods and also array transformations of `$data` field.
     * Applies filters and transforms if any given in subclass.
     *
     * @note This method must be overridden if subclass business differs.
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        if ($data = $this->data) {
            if ($this->filters) {
                $data = $this->filterFields($data);
            }
            if ($this->transforms) {
                $data = $this->transformFields($data);
            }
        }

        return [
            'status' => $this->status,
            'data'   => $data,
            'meta'   => $this->meta,
            'error'  => $this->error
        ];
    }

    /**
     * Convert this resource to JSON string.
     *
     * @note This method must be overridden if subclass business differs.
     * @inheritDoc froq\common\interface\Jsonable
     */
    public function toJson(int $flags = 0): string
    {
        return json_serialize($this->toArray(), (int) $this->options['indent']);
    }

    /**
     * Convert this resource to JsonPayload instance.
     *
     * @return froq\http\response\payload\JsonPayload
     */
    public function toJsonPayload(): JsonPayload
    {
        $attributes = [];
        if ($this->options['indent']) {
            $attributes['indent'] = (int) $this->options['indent'];
        }

        return new JsonPayload($this->status, $this->toArray(), $attributes);
    }

    /**
     * Assign data field.
     *
     * @param  array|null $data
     * @return self
     */
    public function withData(array|null $data): self
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
    public function withMeta(array|null $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Assign error field.
     *
     * @param  mixed $error
     * @return self
     */
    public function withError(mixed $error): self
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
