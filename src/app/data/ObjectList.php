<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

/**
 * A list class, for collecting DTO/VO instances.
 *
 * @package froq\app\data
 * @class   froq\app\data\ObjectList
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
abstract class ObjectList extends \ItemList
{
    /** Option for subclasses. */
    protected bool $convertItems = true;

    /**
     * Constructor.
     *
     * @param array<array|object> $items
     */
    public function __construct(array $items = [])
    {
        [$typeClass, $itemClass] = $this->sniffItemClass();

        if ($items && $itemClass && $this->convertItems) {
            $items = $this->convertItems($items, $itemClass);
        }

        parent::__construct($items, type: $typeClass);
    }

    /**
     * Sniff item class extracting name from subclass name.
     */
    private function sniffItemClass(): array
    {
        $class = match (true) {
            $this instanceof DataObjectList  => DataObject::class,
            $this instanceof ValueObjectList => ValueObject::class,
            default                          => null
        };

        $typeClass = $class;
        $itemClass = null;

        if (str_ends_with($this::class, 'List')) {
            $subclass = substr($this::class, 0, -4);

            if (class_exists($subclass)) {
                $supclass = $typeClass;

                if ($supclass && class_extends($subclass, $supclass)) {
                    $itemClass = $subclass;
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
