<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

use froq\common\interface\Arrayable;

/**
 * Base/template class of DTO (Data Transfer Object) classes, provides basic methods
 * such as update/validate with set/get & array methods and other abstractions like
 * toInput/toOutput.
 *
 * @package froq\app\data
 * @object  froq\app\data\DataObject
 * @author  Kerem Güneş
 * @since   6.0
 */
abstract class DataObject implements Arrayable
{
    /**
     * Constructor.
     *
     * @param mixed ...$properties As map of named arguments.
     */
    public function __construct(mixed ...$properties)
    {
        foreach ($properties as $name => $value) {
            $this->set($name, $value);
        }
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
        if ($this->canUpdateProperty($name)) {
            $this->$name = $value;
        }

        return $this;
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
     * Validate subclass's property data, return errors if validation fails.
     *
     * @param  array|null   $options
     * @param  mixed     ...$arguments
     * @return array|null
     */
    public function validate(array $options = null, mixed ...$arguments): array|null
    {
        $data = (new InputCollector($this))->collect();
        $okay = (new InputValidator($this))->validate($data, $errors, $options, $arguments);

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
     * @param  array<string, mixed> $data
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
     * Check whether a property can be updated controlling that the property is defined
     * in subclass as public & non-static and not in given skip list.
     */
    private function canUpdateProperty(string $name, array $namesToSkip = []): bool
    {
        if (in_array($name, $namesToSkip, true)
            || !property_exists($this, $name)) {
            return false;
        }

        $ref = new \ReflectionProperty($this, $name);
        return $ref->isPublic() && !$ref->isStatic();
    }
}
