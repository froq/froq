<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\common\interface\Arrayable;

/**
 * Base/template class of VO (Value Object) classes, provides basic methods such as
 * set/get & array methods with dynamic property assignment functionality.
 *
 * @package froq\app\data
 * @class   froq\app\data\ValueObject
 * @author  Kerem Güneş
 * @since   6.0
 */
abstract class ValueObject extends \stdClass implements Arrayable
{
    /**
     * Constructor.
     *
     * @param mixed ...$properties Map of named arguments.
     */
    public function __construct(mixed ...$properties)
    {
        foreach ($properties as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Set a property available.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return self
     */
    public function set(string $name, mixed $value): self
    {
        if ($this->canAccessProperty($name)) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Get a property available.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if ($this->canAccessProperty($name)) {
            return $this->$name ?? $default;
        }

        return $default;
    }

    /**
     * Create a VO instance & update with given data.
     *
     * Note: If subclass has a constructor that takes various arguments (any required),
     * than this method must be overridden.
     *
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }

    /**
     * Return data as array.
     *
     * Note: If subclass method logic is different from this method, then this method
     * must be overridden.
     *
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        // Filter private/protected stuff.
        return array_filter_keys((array) $this,
            fn($key): bool => !str_contains((string) $key, "\0"));
    }

    /**
     * Check whether a property can be updated controlling that the property is absent
     * or defined in subclass as public & non-static.
     */
    private function canAccessProperty(string $name): bool
    {
        // Yes - dynamic properties.
        if (!property_exists($this, $name)) {
            return true;
        }

        // @tome: Some cache for props?

        $ref = new \ReflectionProperty($this, $name);
        return $ref->isPublic() && !$ref->isStatic();
    }
}
