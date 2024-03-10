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
            $language = substr($acceptLanguage, 0, 2);

            if (str_contains($acceptLanguage, '-')) {
                $temp = split('-', substr($acceptLanguage, 0, 5), 2);
                return sprintf('%s_%s', $temp[0], strtoupper($temp[1] ?: $temp[0]));
            } else {
                return sprintf('%s_%s', $language, strtoupper($language));
            }
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
            return substr($acceptLanguage, 0, 2);
        }

        return null;
    }

    /**
     * Get accept-language.
     *
     * @return string|null
     * @since  6.0
     */
    public function getAcceptLanguage(): string|null
    {
        return $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    }

    /**
     * Get referer.
     *
     * @return string|null
     * @since  6.0
     */
    public function getReferer(): string|null
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}
