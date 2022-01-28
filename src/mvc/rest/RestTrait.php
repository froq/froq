<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\rest;

use froq\http\exception\client\NotFoundException;

/**
 * Rest Trait.
 *
 * Represents a trait entity that can be used for RESTful controllers that responds only such
 * requests via GET, POST etc. methods and defines those named methods as controller methods.
 *
 * Note: User controller can/must implement `RestInterface` and call `rest()` method in `index()`
 * method.
 *
 * Example for a self index(): `return this.rest(...params)`
 * Example for a parent index(): `if this instanceof RestInterface: return this.rest(...params)`.
 *
 * @package froq\mvc\rest
 * @object  froq\mvc\rest\RestTrait
 * @author  Kerem Güneş
 * @since   4.9
 */
trait RestTrait
{
    /**
     * Calls a rest methods like `get()`, `post()` etc. those defined in user controller.
     *
     * Note: this method must be called in `index()` method.
     *
     * @param  mixed ...$params
     * @return mixed
     * @throws froq\mvc\rest\RestException
     */
    protected final function rest(mixed ...$params): mixed
    {
        $method = $this->request->getMethod();

        if (method_exists($this, $method)) {
            return $this->call($method, $params, suffix: false);
        }

        throw new RestException(
            'No %s() method defined on %s class for %s calls',
            [strtolower($method), $this::class, strtoupper($method)],
            code: NotFoundException::CODE, cause: new NotFoundException()
        );
    }
}
