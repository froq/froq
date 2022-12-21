<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\common;

/**
 * A trait, provides some utilities for request class.
 *
 * @package froq\http\common
 * @class   froq\http\common\RequestTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait RequestTrait
{
    use HeaderTrait, CookieTrait, ParamTrait {
        ParamTrait::cookie insteadof CookieTrait;
        ParamTrait::hasCookie insteadof CookieTrait;
    }

    /**
     * Is get.
     *
     * @return bool
     * @since  4.3
     */
    public function isGet(): bool
    {
        return $this->method->isGet();
    }

    /**
     * Is post.
     *
     * @return bool
     * @since  4.3
     */
    public function isPost(): bool
    {
        return $this->method->isPost();
    }

    /**
     * Is put.
     *
     * @return bool
     * @since  4.3
     */
    public function isPut(): bool
    {
        return $this->method->isPut();
    }

    /**
     * Is patch.
     *
     * @return bool
     * @since  4.3
     */
    public function isPatch(): bool
    {
        return $this->method->isPatch();
    }

    /**
     * Is delete.
     *
     * @return bool
     * @since  4.3
     */
    public function isDelete(): bool
    {
        return $this->method->isDelete();
    }

    /**
     * Is purge.
     *
     * @return bool
     * @since  4.3
     */
    public function isPurge(): bool
    {
        return $this->method->isPurge();
    }

    /**
     * Is options.
     *
     * @return bool
     * @since  4.3
     */
    public function isOptions(): bool
    {
        return $this->method->isOptions();
    }

    /**
     * Is head.
     *
     * @return bool
     * @since  4.3
     */
    public function isHead(): bool
    {
        return $this->method->isHead();
    }

    /**
     * Is trace.
     *
     * @return bool
     * @since  4.3
     */
    public function isTrace(): bool
    {
        return $this->method->isTrace();
    }

    /**
     * Is connect.
     *
     * @return bool
     * @since  4.3
     */
    public function isConnect(): bool
    {
        return $this->method->isConnect();
    }

    /**
     * Is copy.
     *
     * @return bool
     * @since  4.3
     */
    public function isCopy(): bool
    {
        return $this->method->isCopy();
    }

    /**
     * Is move.
     *
     * @return bool
     * @since  4.3
     */
    public function isMove(): bool
    {
        return $this->method->isMove();
    }

    /**
     * Is link.
     *
     * @return bool
     * @since  4.3, 6.0
     */
    public function isLink(): bool
    {
        return $this->method->isLink();
    }

    /**
     * Is unlink.
     *
     * @return bool
     * @since  4.3, 6.0
     */
    public function isUnlink(): bool
    {
        return $this->method->isUnlink();
    }

    /**
     * Is ajax.
     *
     * @return bool
     * @since  4.4
     */
    public function isAjax(): bool
    {
        return $this->method->isAjax();
    }
}
