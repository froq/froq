<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\common;

use froq\http\request\Params;

/**
 * A trait, provides param utilities for request class.
 *
 * @package froq\http\common
 * @class   froq\http\common\ParamTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait ParamTrait
{
    /**
     * Get one/many/all $_GET params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options
     * @return mixed
     */
    public function get(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return Params::get($name, $default, ...$options);
    }

    /**
     * Get one $_GET param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public function getParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return Params::get($name, $default, ...$options);
    }

    /**
     * Get many/all $_GET params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public function getParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return Params::get($names, $defaults, ...$options);
    }

    /**
     * Check one/many/all $_GET params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public function hasGet(string|array $name = null): bool
    {
        return Params::hasGet($name);
    }

    /**
     * Check one $_GET param.
     *
     * @param  string $name
     * @return bool
     */
    public function hasGetParam(string $name): bool
    {
        return Params::hasGet($name);
    }

    /**
     * Check many/all $_GET params.
     *
     * @param  array<string>|null $names
     * @return bool
     */
    public function hasGetParams(array $names = null): bool
    {
        return Params::hasGet($names);
    }

    /**
     * Get one/many/all $_POST params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options
     * @return mixed
     */
    public function post(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return Params::post($name, $default, ...$options);
    }

    /**
     * Get one $_POST param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public function postParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return Params::post($name, $default, ...$options);
    }

    /**
     * Get many/all $_POST params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public function postParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return Params::post($names, $defaults, ...$options);
    }

    /**
     * Check one/many/all $_POST params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public function hasPost(string|array $name = null): bool
    {
        return Params::hasPost($name);
    }

    /**
     * Check one $_POST param.
     *
     * @param  string $name
     * @return bool
     */
    public function hasPostParam(string $name): bool
    {
        return Params::hasPost($name);
    }

    /**
     * Check many/all $_POST params.
     *
     * @param  array<string>|null $names
     * @return bool
     */
    public function hasPostParams(array $names = null): bool
    {
        return Params::hasPost($names);
    }

    /**
     * Get one/many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options
     * @return mixed
     */
    public function cookie(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return Params::cookie($name, $default, ...$options);
    }

    /**
     * Get one $_COOKIE param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public function cookieParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return Params::cookie($name, $default, ...$options);
    }

    /**
     * Get many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public function cookieParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return Params::cookie($names, $defaults, ...$options);
    }

    /**
     * Check one/many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public function hasCookie(string|array $name = null): bool
    {
        return Params::hasCookie($name);
    }

    /**
     * Check one $_COOKIE param.
     *
     * @param  string $name
     * @return bool
     */
    public function hasCookieParam(string $name): bool
    {
        return Params::hasCookie($name);
    }

    /**
     * Check many/all $_COOKIE params.
     *
     * @param  array<string>|null $names
     * @return bool
     */
    public function hasCookieParams(array $names = null): bool
    {
        return Params::hasCookie($names);
    }

    /**
     * Get a segment param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @return mixed
     */
    public function segmentParam(string $name, mixed $default = null): mixed
    {
        return $this->uri->segment($name, $default);
    }

    /**
     * Get many segment params.
     *
     * @param  array<string>|null $names
     * @param  array|null         $defaults
     * @return array
     */
    public function segmentParams(array $names = null, array $defaults = null): array
    {
        return $this->uri->segments($names, $defaults);
    }
}
