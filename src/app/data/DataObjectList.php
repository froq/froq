<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

/**
 * A list class, for collecting DTO instances.
 *
 * @package froq\app\data
 * @object  froq\app\data\DataObjectList
 * @author  Kerem Güneş
 * @since   6.0
 */
class DataObjectList extends ObjectList
{
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
}
