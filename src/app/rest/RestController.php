<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\rest;

use froq\http\exception\client\NotFoundException;

/**
 * Base class of `app\controller` classes (RESTful classes).
 *
 * @package froq\app\rest
 * @object  froq\app\rest\RestTrait
 * @author  Kerem Güneş
 * @since   4.9, 6.0
 */
class RestController extends \froq\app\Controller
{
    /**
     * Call REST related methods like `get()`, `post()` etc. defined in user controller.
     *
     * Note: This method can be called in `index()` method redirecting all routes to this
     * method in route config.
     *
     * Example for GET method only: ["/book(/?.*)", ["GET" => "Book"]].
     * Example for GET and POST methods only: ["/book(/?.*)", ["GET,POST" => "Book"]].
     * Example for all methods: ["/book(/?.*)", "Book"] or ["/book(/?.*)", ["*" => "Book"]].
     *
     * @param  mixed ...$params
     * @return mixed
     * @throws froq\app\rest\RestControllerException
     */
    public final function rest(mixed ...$params): mixed
    {
        $method = $this->request->getMethod();

        if (method_exists($this, $method)) {
            return $this->call($method, $params, suffix: false);
        }

        throw new RestControllerException(
            'No %s() method defined in %s class for %s calls',
            [strtolower($method), static::class, strtoupper($method)],
            code: NotFoundException::CODE, cause: new NotFoundException()
        );
    }
}
