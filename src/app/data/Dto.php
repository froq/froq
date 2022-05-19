<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

use froq\common\interface\Arrayable;

/**
 * Base/template class of DTO classes, provides basic methods such as update/validate
 * with set/get & array methods and other abstractions like toInput/toOutput.
 *
 * @package froq\app\data
 * @object  froq\app\data\Dto
 * @author  Kerem Güneş
 * @since   6.0
 */
abstract class Dto implements Arrayable
{
    /**
     * Constructor.
     *
     * @param array<string, mixed>|null $data
     */
    public function __construct(array $data = null)
    {
        $data && $this->update($data);
    }

    /**
     * Set a property if exists.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return self
     */
    public function set(string $name, mixed $value): self
    {
        return $this->update([$name => $value]);
    }

    /**
     * Get a property if exists.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->$name ?? $default;
    }

    /**
     * Update (fill/refill) defined subclass properties using given data.
     *
     * @param  array<string, mixed> $data
     * @param  array<string>        $skip Names to skip/ignore.
     * @return self
     */
    public function update(array $data, array $skip = []): self
    {
        foreach ($data as $name => $value) {
            if ($this->canUpdateProperty($name, $skip)) {
                $this->$name = $value;
            }
        }

        return $this;
    }

    /**
     * Validate subclass properties data, return errors if validation fails.
     *
     * @param  array|null   $options
     * @param  mixed     ...$arguments
     * @return array|null
     */
    public function validate(array $options = null, mixed ...$arguments): array|null
    {
        $data = (new DataCollector($this))->collect();
        $okay = (new DataValidator($this))->validate($data, $errors, $options, $arguments);

        // Update self data with validated data.
        $okay && $this->update($data);

        return $errors;
    }

    /**
     * Create a DTO instance & update with given data.
     *
     * Note: If subclass has a constructor that takes various arguments (any required),
     * than this method must be overriden.
     *
     * @param  array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return (new static)->update($data);
    }

    /**
     * Return output data as default.
     *
     * Note: If subclass method logic is different from this method, then this method
     * must be overriden.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->toOutput();
    }

    /**
     * Prepare input/incoming data.
     *
     * @return mixed
     */
    abstract public function toInput(): mixed;

    /**
     * Prepare output/outgoing data.
     *
     * @return mixed
     */
    abstract public function toOutput(): mixed;

    /**
     * Check whether a property can be updated.
     */
    private function canUpdateProperty(string $name, array $namesToSkip): bool
    {
        if (in_array($name, $namesToSkip, true)
            || !property_exists($this, $name)) {
            return false;
        }

        $ref = new \ReflectionProperty($this, $name);
        return $ref->isPublic() && !$ref->isStatic();
    }
}