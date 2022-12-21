<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http;

/**
 * @package froq\http
 * @class   froq\http\HttpException
 * @author  Kerem Güneş
 * @since   1.0
 */
class HttpException extends \froq\common\Exception
{
    /**
     * Prepare code & message for subclasses.
     *
     * @param  int|null    $code
     * @param  string|null $message
     * @return array
     * @since  5.0, 6.0
     */
    protected static function prepare(int|null $code, string|null $message): array
    {
        // Overwrite on code with child class code.
        if (defined(static::class . '::CODE')) {
            $code = static::CODE;
        }

        if ($code >= 400 && $message === null) {
            $message = response\Status::getTextByCode($code);
            $message && $message = ucfirst(strtolower($message));
        }

        return [$code, $message];
    }
}
