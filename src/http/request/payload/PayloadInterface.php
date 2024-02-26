<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

/**
 * @package froq\http\request\payload
 * @class   froq\http\request\payload\PayloadInterface
 * @author  Kerem Güneş
 * @since   7.3
 */
interface PayloadInterface
{
    /**
     * Get a field.
     *
     * @param  string     $key
     * @param  mixed|null $default
     * @return mixed|null
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get many fields.
     *
     * @param  array      $keys
     * @param  array|null $defaults
     * @return array
     */
    public function getAll(array $keys, array $defaults = null): array;
}
