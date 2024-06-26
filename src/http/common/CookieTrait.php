<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\common;

/**
 * A trait, provides cookie utilities for request/response classes.
 *
 * @package froq\http\common
 * @class   froq\http\common\CookieTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait CookieTrait
{
    /**
     * Set/get a cookie.
     *
     * @param  string          $name
     * @param  string|int|null $value
     * @param  array|null      $options
     * @return mixed
     */
    public function cookie(string $name, string|int $value = null, array $options = null): mixed
    {
        if (func_num_args() === 1) {
            return $this->getCookie($name);
        }

        return $this->setCookie($name, $value, $options);
    }

    /**
     * Check a cookie existence.
     *
     * @param  string $name
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return $this->cookies->has($name);
    }

    /**
     * Add a cookie.
     *
     * @alias setCookie()
     */
    public function addCookie(...$args)
    {
        return $this->setCookie(...$args);
    }

    /**
     * Set a cookie.
     *
     * @param  string          $name
     * @param  string|int|null $value
     * @param  array|null      $options
     * @return self
     * @throws Error
     */
    public function setCookie(string $name, string|int|null $value, array $options = null): self
    {
        if ($this->isRequest()) {
            throw new \Error('Cannot modify request cookies');
        }

        $this->cookies->set($name, ['value' => $value, 'options' => $options]);

        return $this;
    }

    /**
     * Get a cookie.
     *
     * @param  string                $name
     * @param  string|int|array|null $default
     * @return string|int|array|null
     */
    public function getCookie(string $name, string $default = null): string|int|array|null
    {
        return $this->cookies->get($name, $default);
    }

    /**
     * Remove a cookie.
     *
     * @param  string     $name
     * @param  array|null $options
     * @return self
     * @throws Error
     */
    public function removeCookie(string $name, array $options = null): self
    {
        if ($this->isRequest()) {
            throw new \Error('Cannot modify request cookies');
        }

        if (!$options && $this->hasCookie($name)) {
            $current = $this->getCookie($name);
            if (isset($current['options'])) {
                $options = $current['options'];
            }
        }

        // Mark as removed.
        $this->setCookie($name, null, $options);

        return $this;
    }
}
