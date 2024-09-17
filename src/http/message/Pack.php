<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\message;

use froq\util\package\Package;

/**
 * A pack(age) class used by Headers & Cookies classes.
 *
 * @package froq\http\message
 * @class   froq\http\message\Pack
 * @author  Kerem Güneş
 * @since   7.0
 * @internal
 */
class Pack extends Package
{
    /**
     * @override
     */
    public function __construct(array $data = [])
    {
        parent::__construct(...$data);
    }

    /**
     * For dumping purpose only.
     *
     * @magic
     */
    public function __debugInfo(): array
    {
        return $this->data;
    }

    /**
     * Search an entry.
     *
     * @param  string $name
     * @return array|null
     */
    public function search(string $name): array|null
    {
        if ($this->offsetExists($name)) {
            return [$name, $this->offsetGet($name)];
        }

        // Try with lower-cased name.
        foreach ($this->data as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return [$name, $value];
            }
        }

        return null;
    }

    /**
     * Check an item.
     *
     * @param  string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->search($name) !== null;
    }

    /**
     * Set an item.
     *
     * @param  string     $name
     * @param  mixed|null $value
     * @return self
     */
    public function set(string $name, mixed $value = null): self
    {
        $this->offsetSet($name, $value);

        return $this;
    }

    /**
     * Get an item.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        [, $value] = $this->search($name);

        return $value ?? $default;
    }

    /**
     * Remove an item.
     *
     * @param  string $name
     * @return bool
     */
    public function remove(string $name): bool
    {
        [$key] = $this->search($name);

        if ($found = isset($key)) {
            $this->offsetUnset($key);
        }

        return $found;
    }

    /**
     * Get names.
     *
     * @return array
     */
    public function names(): array
    {
        return array_keys($this->data);
    }

    /**
     * Get values.
     *
     * @return array
     */
    public function values(): array
    {
        return array_values($this->data);
    }
}
