<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

/**
 * Collector class, collects property data of self DTO instance.
 *
 * @package froq\app\data
 * @object  froq\app\data\DataCollector
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
class DataCollector
{
    /**
     * Constructor.
     *
     * @param froq\app\data\Dto $dto
     */
    public function __construct(
        public readonly Dto $dto
    ) {}

    /**
     * Bridge method for DTO's `toInput()` method.
     *
     * @return array
     */
    public function collect(): array
    {
        return $this->dto->toInput();
    }

    /**
     * Collect "public" properties data of self DTO.
     *
     * @return array
     */
    public function collectProperties(): array
    {
        return get_object_vars($this->dto);
    }
}
