<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

use froq\common\interface\Stringable;

/**
 * Method class, used by request class.
 *
 * @package froq\http\request
 * @class   froq\http\request\Method
 * @author  Kerem Güneş
 * @since   1.0
 */
class Method implements Stringable, \Stringable
{
    /** Names. */
    public const GET     = 'GET',     POST    = 'POST',
                 PUT     = 'PUT',     PATCH   = 'PATCH',
                 DELETE  = 'DELETE',  PURGE   = 'PURGE',
                 OPTIONS = 'OPTIONS', HEAD    = 'HEAD',
                 TRACE   = 'TRACE',   CONNECT = 'CONNECT',
                 COPY    = 'COPY',    MOVE    = 'MOVE',
                 LINK    = 'LINK',    UNLINK  = 'UNLINK';

    /** Name. */
    private string $name;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * @magic
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Set name.
     *
     * @param  string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = strtoupper($name);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is get.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return ($this->name === self::GET);
    }

    /**
     * Is post.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return ($this->name === self::POST);
    }

    /**
     * Is put.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return ($this->name === self::PUT);
    }

    /**
     * Is patch.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return ($this->name === self::PATCH);
    }

    /**
     * Is delete.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return ($this->name === self::DELETE);
    }

    /**
     * Is purge.
     *
     * @return bool
     */
    public function isPurge(): bool
    {
        return ($this->name === self::PURGE);
    }

    /**
     * Is options.
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return ($this->name === self::OPTIONS);
    }

    /**
     * Is head.
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return ($this->name === self::HEAD);
    }

    /**
     * Is trace.
     *
     * @return bool
     */
    public function isTrace(): bool
    {
        return ($this->name === self::TRACE);
    }

    /**
     * Is connect.
     *
     * @return bool
     */
    public function isConnect(): bool
    {
        return ($this->name === self::CONNECT);
    }

    /**
     * Is copy.
     *
     * @return bool
     */
    public function isCopy(): bool
    {
        return ($this->name === self::COPY);
    }

    /**
     * Is move.
     *
     * @return bool
     */
    public function isMove(): bool
    {
        return ($this->name === self::MOVE);
    }

    /**
     * Is link.
     *
     * @return bool
     */
    public function isLink(): bool
    {
        return ($this->name === self::LINK);
    }

    /**
     * Is unlink.
     *
     * @return bool
     */
    public function isUnlink(): bool
    {
        return ($this->name === self::UNLINK);
    }

    /**
     * Is ajax.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        }
        if (isset($_SERVER['HTTP_X_AJAX'])) {
            return strtolower($_SERVER['HTTP_X_AJAX']) === 'true' || $_SERVER['HTTP_X_AJAX'] === '1';
        }
        return false;
    }

    /**
     * @inheritDoc froq\common\interface\Stringable
     */
    public function toString(): string
    {
        return $this->name;
    }
}
