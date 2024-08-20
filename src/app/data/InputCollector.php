<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\common\interface\Arrayable;

/**
 * Collector class, collects property data of given DTO instance.
 *
 * @package froq\app\data
 * @class   froq\app\data\InputCollector
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
class InputCollector
{
    /**
     * Constructor.
     *
     * @param object $do Source data object.
     */
    public function __construct(
        public readonly object $do
    ) {}

    /**
     * Collect property data of DTO instance.
     *
     * @return array
     */
    public function collect(): array
    {
        if ($this->do instanceof DataObject) {
            return $this->do->toInput();
        }
        if ($this->do instanceof Arrayable) {
            return $this->do->toArray();
        }
        return $this->collectVars();
    }

    /**
     * Collect "public" property data of DTO instance.
     *
     * @return array
     */
    public function collectVars(): array
    {
        return get_object_vars($this->do);
    }
}
