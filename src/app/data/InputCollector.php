<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

/**
 * Collector class, collects property data of given DTO instance.
 *
 * @package froq\app\data
 * @object  froq\app\data\InputCollector
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
class InputCollector
{
    /**
     * Constructor.
     *
     * @param froq\app\data\Data $data
     */
    public function __construct(
        public readonly Data $data
    ) {}

    /**
     * Bridge method for DTO's `toInput()` method.
     *
     * @return array
     */
    public function collect(): array
    {
        return $this->data->toInput();
    }

    /**
     * Collect "public" property data of given DTO instance.
     *
     * @return array
     */
    public function collectProperties(): array
    {
        return get_object_vars($this->data);
    }
}
