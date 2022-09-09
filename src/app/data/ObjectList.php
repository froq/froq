<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

use froq\pager\{Pager, PagerTrait};

/**
 * A list class, for collecting DTO/VO instances.
 *
 * @package froq\app\data
 * @object  froq\app\data\ObjectList
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
class ObjectList extends \ItemList
{
    use PagerTrait;

    /** @var bool */
    public bool $convertItems = true;

    /**
     * Constructor.
     *
     * @param  array<array|object>   $items
     * @param  froq\pager\Pager|null $pager
     * @throws ValueError
     */
    public function __construct(array $items = [], Pager $pager = null)
    {
        if ($items) {
            is_list($items) || throw new \ValueError(
                'Given items must be list, map given'
            );

            // Sniff item class for converting items to DTO/VO instances.
            if ($this->convertItems && ($itemClass = $this->sniffItemClass())) {
                $items = $this->convertItems($items, $itemClass);
            }
        }

        parent::__construct($items);

        $this->pager = $pager;
    }

    /**
     * Sniff item class extracting name from subclass name.
     */
    private function sniffItemClass(): string|null
    {
        if (str_ends_with(static::class, 'List')) {
            $class = substr(static::class, 0, -4);

            if (class_exists($class)) {
                $parent = match (true) {
                    $this instanceof DataObjectList => DataObject::class,
                    $this instanceof ValueObjectList => ValueObject::class,
                    default => null
                };

                if ($parent && class_extends($class, $parent)) {
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * Convert items to DTO/VO instances.
     */
    private function convertItems(array $items, string $itemClass): array
    {
        $object = new $itemClass;
        foreach ($items as &$item) {
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
