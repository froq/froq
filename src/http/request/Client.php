<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

use froq\util\Util;

/**
 * An accessor class, for accessing some client properties.
 *
 * @package froq\http\request
 * @class   froq\http\request\Client
 * @author  Kerem Güneş
 * @since   1.0
 */
class Client
{
    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Get ip.
     *
     * @return string|null
     */
    public function getIp(): string|null
    {
        return Util::getClientIp();
    }

    /**
     * Get user agent.
     *
     * @param  bool $safe
     * @return string|null
     */
    public function getUserAgent(bool $safe = true): string|null
    {
        return Util::getClientUa($safe);
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale(): string|null
    {
        $acceptLanguage = $this->getAcceptLanguage();

        if ($acceptLanguage) {
            preg_match('~^([a-z]+)(?:[_-]([a-z]+)|[,;]*)?~',
                strtolower($acceptLanguage), $match, PREG_UNMATCHED_AS_NULL);

            return $match[1] . '_' . strtoupper($match[2] ?: $match[1]);
        }

        return null;
    }

    /**
     * Get language.
     *
     * @return string|null
     */
    public function getLanguage(): string|null
    {
        $acceptLanguage = $this->getAcceptLanguage();

        if ($acceptLanguage) {
            return preg_replace('~^([a-z]+)(?:[_-]|[,;])?.*~', '\1',
                strtolower($acceptLanguage));
        }

        return null;
    }

    /**
     * Get accept-language.
     *
     * @return string|null
     */
    public function getAcceptLanguage(): string|null
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $ret = trim((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']);

            if (strlen($ret) >= 2) {
                return $ret;
            }
        }

        return null;
    }

    /**
     * Get referer.
     *
     * @return string|null
     */
    public function getReferer(): string|null
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}
