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
 * created for these errors in client `end()` method.
 *
 * @package froq\http\client\curl
 * @class   froq\http\client\curl\CurlResponseError
 * @author  Kerem Güneş
 * @since   5.0
 */
class CurlResponseError extends \froq\common\Error
{
    /** Status code. */
    public readonly int $status;

    /** Request instance. */
    private ?Request $request = null;

    /** Response instance. */
    private ?Response $response = null;

    /**
     * Constructor.
     *
     * @param int      $status
     * @param mixed ...$arguments
     * @since 6.0
     */
    public function __construct(int $status, mixed ...$arguments)
    {
        $this->status = $status;

        // Update code.
        $arguments['code'] = $status;

        parent::__construct(...$arguments);
    }

    /**
     * Set request.
     *
     * @param  froq\http\client\Request $request
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Get request.
     *
     * @return froq\http\client\Request|null
     */
    public function getRequest(): Request|null
    {
        return $this->request;
    }

    /**
     * Set response.
     *
     * @param  froq\http\client\Response $response
     * @return void
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * Get response.
     *
     * @return froq\http\client\Response|null
     */
    public function getResponse(): Response|null
    {
        return $this->response;
    }
}
