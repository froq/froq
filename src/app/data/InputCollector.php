<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

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
     * @param froq\app\data\DataObject $do
     */
    public function __construct(
        public readonly DataObject $do
    ) {}

    /**
     * Bridge method for DTO's `toInput()` method.
     *
     * @return array
     */
    public function collect(): array
    {
        return $this->do->toInput();
    }

    /**
     * Collect "public" property data of given DTO instance.
     *
     * @return array
     */
    public function collectVars(): array
    {
        return get_object_vars($this->do);
    }
}
