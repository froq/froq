<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\exception;

/**
 * @package froq\http\exception
 * @class   froq\http\exception\ServerException
 * @author  Kerem Güneş
 * @since   5.0
 */
class ServerException extends \froq\http\HttpException
{
    /**
     * Constructor.
     *
     * @param  int|null    $code
     * @param  string|null $message
     * @param  mixed|null  $messageParams
     * @param  mixed    ...$arguments
     * @throws froq\http\HttpException
     */
    public function __construct(int $code = null, string $message = null, mixed $messageParams = null, mixed ...$arguments)
    {
        if ($code !== null) {
            // Forbid code assigns for internal classes.
            if (static::class !== self::class && str_starts_with(static::class, __NAMESPACE__)) {
                throw new parent(
                    'Cannot set $code parameter for %s, it\'s already set internally',
                    static::class
                );
            }

            // Forbid invalid code assigns.
            if ($code < 500 || $code > 599) {
                throw new parent(
                    'Invalid server exception code %s, it must be between 500-599',
                    $code
                );
            }
        }

        [$code, $message] = parent::prepare($code, $message);

        $arguments = [$message, $messageParams, $code, ...$arguments];

        parent::__construct(...$arguments);
    }
}
