<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\message;

/**
 * @package froq\http\message
 * @class   froq\http\message\Headers
 * @author  Kerem Güneş
 * @since   4.0
 */
class Headers extends Pack
{
    /**
     * @override
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data, true);
    }

    /**
     * Drop ref (&) and allow camel-case names.
     *
     * @override
     */
    public function &__get(int|string $name): mixed
    {
        // No ref anymore.
        $value = $this->_get($name);

        return $value;
    }

    /**
     * Drop ref (&) and allow lower-case names.
     *
     * @override
     */
    public function &offsetGet(mixed $name): mixed
    {
        // No ref anymore.
        $value = $this->_getOffset($name);

        return $value;
    }

    /**
     * @internal
     */
    private function _get(string $name): mixed
    {
        $value = $this->get($name);

        // Try camel-case.
        if ($value === null) {
            $name = preg_replace_callback(
                '~[A-Z]~', fn($m) => '-' . strtolower($m[0]), $name
            );

            $value = $this->get($name);
        }

        return $value;
    }

    /**
     * @internal
     */
    private function _getOffset(string $name): mixed
    {
        $value = $this->get($name);

        return $value;
    }
}
