<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

use froq\pager\{Pager, PagerTrait};

/**
 * A list class, for collecting DTO instances.
 *
 * @package froq\app\data
 * @object  froq\app\data\DataObjectList
 * @author  Kerem Güneş
 * @since   6.0
 */
class DataObjectList extends \ItemList
{
    use PagerTrait;

    /** @var bool */
    public bool $convertItems = true;

    /**
     * Constructor.
     *
     * @param array                 $items
     * @param froq\pager\Pager|null $pager
     */
    public function __construct(array $items = [], Pager $pager = null)
    {
        // Sniff item class for converting items to DTOs.
        if ($this->convertItems && ($itemClass = $this->sniffItemClass())) {
            $items = $this->convertItems($items, $itemClass);
        }

        parent::__construct($items);

        $this->pager = $pager;
    }

    /**
     * @override
     */
    public function toArray(bool $deep = true): array
    {
        $items = parent::toArray();

        if ($deep) foreach ($items as &$item) {
            if ($item instanceof DataObject) {
                $item = $item->toArray();
            }
        }

        return $items;
    }

    /**
     * Sniff item class extracting name from subclass name.
     */
    private function sniffItemClass(): string|null
    {
        if (str_ends_with(static::class, 'List')) {
            $class = substr(static::class, 0, -4);
            if (class_exists($class)
                && class_extends($class, DataObject::class)) {
                return $class;
            }
        }
        return null;
    }

    /**
     * Convert items to DTO instances.
     */
    private function convertItems(array $items, string $itemClass): array
    {
        $object = new $itemClass;
        foreach ($items as &$item) {
            if (is_array($item)) {
                $item = (clone $object)->update($item);
            }
        }
        return $items;
    }
}