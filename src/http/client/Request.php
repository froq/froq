<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client;

/**
 * A client request class.
 *
 * @package froq\http\client
 * @class   froq\http\client\Request
 * @author  Kerem Güneş
 * @since   3.0
 */
class Request extends Message
{
    /** Method. */
    private string $method;

    /** URL. */
    private string $url;

    /** URL params. */
    private ?array $urlParams = null;

    /** Default headers. */
    private static array $headersDefault = [
        'accept'          => '*/*',
        'accept-encoding' => 'gzip',
        'user-agent'      => 'Froq HTTP Client (+http://github.com/froq/froq)',
    ];

    /**
     * Constructor.
     *
     * @param string      $method
     * @param string      $url
     * @param array|null  $urlParams
     * @param string|null $body
     * @param array|null  $headers
     */
    public function __construct(string $method, string $url, array $urlParams = null, string $body = null,
        array $headers = null)
    {
        $this->setMethod($method)
             ->setUrl($url)
             ->setUrlParams($urlParams);

        // Merge & normalize headers.
        $headers = array_replace_recursive(self::$headersDefault, (array) $headers);
        $headers = array_lower_keys($headers);

        parent::__construct(null, $headers, $body);
    }

    /**
     * Set method.
     *
     * @param  string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Get method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set URL.
     *
     * @param  string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set URL params.
     *
     * @param  array|null $urlParams
     * @return self
     */
    public function setUrlParams(array|null $urlParams): self
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * Get URL params.
     *
     * @return array|null
     */
    public function getUrlParams(): array|null
    {
        return $this->urlParams;
    }

    /**
     * Get URI.
     *
     * @return string
     * @internal
     */
    protected function getUri(): string
    {
        // Extract the only path and query part of URL.
        return preg_replace('~^\w+://[^/]+(/.*)~', '\1', $this->getUrl());
    }
}
