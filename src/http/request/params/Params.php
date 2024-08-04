<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\params;

use froq\http\message\Pack;
use froq\collection\trait\GetTrait;

/**
 * @package froq\http\request\params
 * @class   froq\http\request\params\Params
 * @author  Kerem Güneş
 * @since   7.0
 */
class Params extends Pack
{
    use GetTrait;

    /**
     * Map.
     *
     * @param  callable $func
     * @param  bool     $recursive
     * @param  bool     $useKeys
     * @return self
     */
    public function map(callable $func, bool $recursive = false, bool $useKeys = false): self
    {
        $this->data = map($this->data, $func, $recursive, $useKeys);

        return $this;
    }

    /**
     * Filter.
     *
     * @param  callable $func
     * @param  bool     $recursive
     * @param  bool     $useKeys
     * @return self
     */
    public function filter(callable $func, bool $recursive = false, bool $useKeys = false): self
    {
        $this->data = filter($this->data, $func, $recursive, $useKeys);

        return $this;
    }

    /**
     * Reduce.
     *
     * @param  mixed         $carry
     * @param  callable|null $func
     * @param  bool          $right
     * @return mixed
     */
    public function reduce(mixed $carry, callable $func = null, bool $right = false): mixed
    {
        return reduce($this->data, $carry, $func, $right);
    }
}
