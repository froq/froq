<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\common;

/**
 * A trait, provides header utilities for request/response classes.
 *
 * @package froq\http\common
 * @class   froq\http\common\HeaderTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait HeaderTrait
{
    /**
     * Set/get/add a header.
     *
     * @param  string          $name
     * @param  string|int|null $value
     * @param  bool            $replace
     * @return mixed
     */
    public function header(string $name, string|int $value = null, bool $replace = true): mixed
    {
        if (func_num_args() === 1) {
            return $this->getHeader($name);
        }

        return $replace ? $this->setHeader($name, $value)
                        : $this->addHeader($name, $value);
    }

    /**
     * Check a header existence.
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * Add a header.
     *
     * @param  string                $name
     * @param  string|int|array|null $value
     * @return self
     * @throws Error
     */
    public function addHeader(string $name, string|int|array|null $value): self
    {
        if ($this->isRequest()) {
            throw new \Error('Cannot modify request headers');
        }

        // Multi-headers (eg: Link, Cookie).
        if ($this->headers->has($name)) {
            $value = array_map('strval', array_merge(
                (array) $this->headers->get($name),
                (array) $value
            ));
        }

        $this->headers->set($name, $value);

        return $this;
    }

    /**
     * Set a header.
     *
     * @param  string                $name
     * @param  string|int|array|null $value
     * @return self
     * @throws Error
     */
    public function setHeader(string $name, string|int|array|null $value): self
    {
        if ($this->isRequest()) {
            throw new \Error('Cannot modify request headers');
        }

        $this->headers->set($name, $value);

        return $this;
    }

    /**
     * Get a header.
     *
     * @param  string                $name
     * @param  string|int|array|null $default
     * @return string|int|array|null
     */
    public function getHeader(string $name, string|int|array $default = null): string|int|array|null
    {
        return $this->headers->get($name, $default);
    }

    /**
     * Remove a header.
     *
     * @param  string $name
     * @return self
     * @throws Error
     */
    public function removeHeader(string $name): self
    {
        if ($this->isRequest()) {
            throw new \Error('Cannot modify request headers');
        }

       $this->headers->remove($name);

        return $this;
    }

    /**
     * Parse a header.
     *
     * @param  string $name
     * @param  bool   $verbose
     * @return array
     * @since  6.0
     */
    public function parseHeader(string $name, bool $verbose = false): array
    {
        return http_parse_header($name . ': ' . $this->getHeader($name), verbose: $verbose);
    }
}
