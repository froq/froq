<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client\curl;

use froq\http\client\{Request, Response};

/**
 * An error class, only thrown when client option `throwHttpErrors` is true or used as
 * client `$error` property when any HTTP error (status code >= 400) occurs and always
 * created for these errors in client `end()` method only.
 *
 * @package froq\http\client\curl
 * @class   froq\http\client\curl\CurlResponseError
 * @author  Kerem Güneş
 * @since   5.0
 */
class CurlResponseError extends \froq\common\Error
{
    /**
     * Constructor.
     *
     * @param int                            $status
     * @param froq\http\client\Request|null  $request
     * @param froq\http\client\Response|null $response
     * @since 6.0
     */
    public function __construct(
        public readonly int       $status,
        public readonly ?Request  $request  = null,
        public readonly ?Response $response = null,
    )
    {
        parent::__construct(code: $status);
    }
}
