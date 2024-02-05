<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\message;

use froq\collection\collector\MapCollector;

/**
 * @package froq\http\message
 * @class   froq\http\message\Cookies
 * @author  Kerem Güneş
 * @since   4.0
 */
class Cookies extends MapCollector
{
    /**
     * For dumping purpose only.
     */
    public function __debugInfo(): array
    {
        return $this->data;
    }

    /**
     * Drop ref (&) and allow camel-case keys.
     *
     * @override
     */
    public function &__get(int|string $key): mixed
    {
        // No ref anymore.
        $value = $this->_value($key);

        return $value;
    }

    /**
     * Get a value of given key (cookie name) with camel-case key support.
     *
     * @internal
     */
    private function _value(string $key): string|null
    {
        $value = $this->get($key);

        // Try camel-case.
        if ($value === null) {
            $key = preg_replace_callback(
                '~[A-Z]~', fn($m) => '-' . strtolower($m[0]), $key
            );

            $value = $this->get($key);
        }

        return $value;
    }
}
