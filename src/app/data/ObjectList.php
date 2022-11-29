<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

/**
 * A list class, for collecting DTO/VO instances.
 *
 * @package froq\app\data
 * @object  froq\app\data\ObjectList
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
abstract class ObjectList extends \ItemList
{
    /**
     * Convert option for subclasses.
     *
     * @var bool
     */
    protected bool $convertItems = true;

    /**
     * Constructor.
     *
     * @param  array<array|object> $items
     * @throws ArgumentError
     */
    public function __construct(array $items = [])
    {
        [$typeClass, $itemClass] = $this->sniffItemClass();

        if ($items) {
            is_list($items) || throw new \ArgumentError(
                'Argument $items must be list, map given'
            );

            // Sniff item class for converting items to DTO/VO instances.
            if ($this->convertItems && $itemClass) {
                $items = $this->convertItems($items, $itemClass);
            }
        }

        parent::__construct($items, type: $typeClass);
    }

    /**
     * Sniff item class extracting name from subclass name.
     */
    private function sniffItemClass(): array
    {
        $typeClass = match (true) {
            $this instanceof DataObjectList  => DataObject::class,
            $this instanceof ValueObjectList => ValueObject::class,
            default                          => null
        };
        $itemClass = null;

        if (str_ends_with($this::class, 'List')) {
            $class = substr($this::class, 0, -4);

            if (class_exists($class)) {
                $parent = match (true) {
                    $this instanceof DataObjectList  => DataObject::class,
                    $this instanceof ValueObjectList => ValueObject::class,
                    default                          => null
                };

                if ($parent && class_extends($class, $parent)) {
                    $itemClass = $class;
                }
            }
        }

        return [$itemClass ?? $typeClass, $itemClass];
    }

    /**
     * Convert items to DTO/VO instances.
     */
    private function convertItems(array $items, string $itemClass): array
    {
        $object = new $itemClass();

        foreach ($items as &$item) {
            if ($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if (!is_array($item)) {
                continue;
            }

            $clone = clone $object;
            foreach ($item as $name => $value) {
                $clone->set((string) $name, $value);
            }

            $item = $clone;
        }

        return $items;
    }
}
