<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

use froq\common\interface\{Arrayable, Listable};

/**
 * Segments class, for parsing / getting URI path segments.
 *
 * @package froq\http\request
 * @class   froq\http\request\Segments
 * @author  Kerem Güneş
 * @since   4.1
 */
class Segments implements Arrayable, Listable, \Countable, \ArrayAccess
{
    /** Default root. */
    public const ROOT = '/';

    /** Segments data. */
    private array $data = [];

    /** Segments root. */
    private string $root = self::ROOT;

    /**
     * Constructor.
     *
     * @param array|null  $data
     * @param string|null $root
     */
    public function __construct(array $data = null, string $root = null)
    {
        $data && $this->data = $data;
        $root && $this->root = $root;
    }

    /**
     * @magic
     */
    public function __set(string $key, string|null $value): void
    {
        $this->offsetSet($key, $value);
    }

    /**
     * @magic
     */
    public function __get(string $key): string|null
    {
        return $this->offsetGet($key);
    }

    /**
     * Get root.
     *
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get params.
     *
     * @return array
     */
    public function params(): array
    {
        return $this->data['params'] ?? [];
    }

    /**
     * Get params list.
     *
     * @return array
     */
    public function paramsList(): array
    {
        return $this->data['paramsList'] ?? [];
    }

    /**
     * Get a segment param.
     *
     * @param  int|string  $key
     * @param  string|null $default
     * @return string|null
     */
    public function get(int|string $key, string $default = null): string|null
    {
        return is_int($key) ? $this->data['paramsList'][$key] ?? $default
                            : $this->data['params'][$key]     ?? $default;
    }

    /**
     * Get a segment param.
     *
     * @param  string      $name
     * @param  string|null $default
     * @return string|null
     */
    public function getParam(string $name, string $default = null): string|null
    {
        return $this->data['params'][$name] ?? $default;
    }

    /**
     * Get many segment params.
     *
     * @param  array<string>|null $names
     * @param  array<string>|null $defaults
     * @return array<string>|null
     */
    public function getParams(array $names = null, array $defaults = null): array|null
    {
        if ($names === null) {
            return $this->data['params'] ?? $defaults;
        }

        $values = [];

        foreach ($names as $i => $name) {
            $values[] = $this->data['params'][$name] ?? $defaults[$i] ?? null;
        }

        return $values;
    }

    /**
     * From array.
     *
     * @param  array<string> $array
     * @param  string|null   $root
     * @return froq\http\request\Segments
     */
    public static function fromArray(array $array, string $root = null): Segments
    {
        $data = ['params' => [], 'paramsList' => []];

        // Chunk as key/value pairs.
        foreach (array_chunk($array, 2) as $dat) {
            $data['params'][$dat[0]] = $dat[1] ?? null;
        }

        // Index from 1, not 0.
        foreach ($array as $i => $dat) {
            $data['paramsList'][$i + 1] = $dat;
        }

        return new Segments($data, $root);
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return $this->params();
    }

    /**
     * @inheritDoc froq\common\interface\Listable
     */
    public function toList(int $start = 0, int $end = null): array
    {
        return slice($this->paramsList(), $start, $end);
    }

    /**
     * @inheritDoc Countable
     */
    public function count(): int
    {
        return count($this->paramsList());
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * @inheritDoc ArrayAccess
     */
    public function offsetGet(mixed $key): string|null
    {
        return $this->get($key);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetSet(mixed $key, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @inheritDoc ArrayAccess
     * @throws     ReadonlyError
     */
    public function offsetUnset(mixed $key): never
    {
        throw new \ReadonlyError($this);
    }
}
