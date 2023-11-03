<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client\curl;

use froq\http\client\Client;
use CurlHandle;

/**
 * A class for handling single cURL opearations & feeding back client.
 *
 * @package froq\http\client\curl
 * @class   froq\http\client\curl\Curl
 * @author  Kerem Güneş
 * @since   3.0
 */
class Curl
{
    /** Blocked options. */
    public const BLOCKED_OPTIONS = [
        'CURLOPT_CUSTOMREQUEST'  => CURLOPT_CUSTOMREQUEST,
        'CURLOPT_URL'            => CURLOPT_URL,
        'CURLOPT_HEADER'         => CURLOPT_HEADER,
        'CURLOPT_RETURNTRANSFER' => CURLOPT_RETURNTRANSFER,
        'CURLOPT_HEADERFUNCTION' => CURLOPT_HEADERFUNCTION,
        'CURLINFO_HEADER_OUT'    => CURLINFO_HEADER_OUT,
    ];

    /** Client instance. */
    private Client $client;

    /** Headers buffer. */
    private string $headers = '';

    /**
     * Constructor.
     *
     * @param froq\http\client\Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $client && $this->setClient($client);
    }

    /**
     * Set client.
     *
     * @param  froq\http\client\Client $client
     * @return self
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return froq\http\client\Client|null
     */
    public function getClient(): Client|null
    {
        return $this->client ?? null;
    }

    /**
     * Get a processed cURL handle info.
     *
     * @param  CurlHandle $handle
     * @return array
     * @since  5.0
     */
    public function getHandleInfo(CurlHandle $handle): array
    {
        $info = curl_getinfo($handle);

        // Add/update headers.
        $info['request_header']  = trim($info['request_header']);
        $info['response_header'] = trim($this->headers);

        // Reset for other requests.
        $this->headers = '';

        return $info;
    }

    /**
     * Run a cURL request.
     *
     * @return void
     * @throws froq\http\client\curl\CurlException
     */
    public function run(): void
    {
        $client = $this->getClient()
            ?: throw new CurlException('No client initiated yet to process');

        $client->setup();

        $handle = &$this->init();

        $result = curl_exec($handle);
        if ($result !== false) {
            $client->end($result, $this->getHandleInfo($handle));
        } else {
            $client->end(null, null, new CurlError(curl_error($handle), code: curl_errno($handle)));
        }

        // Drop handle.
        unset($handle);
    }

    /**
     * Init a cURL handle.
     *
     * @return &CurlHandle
     * @throws froq\http\client\curl\CurlException
     */
    public function &init(): CurlHandle
    {
        $client = $this->getClient()
            ?: throw new CurlException('No client initiated yet to process');

        $handle = curl_init()
            ?: throw new CurlException('Failed curl session [error: @error]');

        $request = $client->getRequest();

        [$method, $url, $headers, $body, $clientOptions] = [
            $request->getMethod(),  $request->getUrl(),
            $request->getHeaders(), $request->getBody(),
            $client->getOptions()
        ];

        $options = [
            // Immutable (internal) options.
            CURLOPT_CUSTOMREQUEST     => $method, // Prepared, set by request object.
            CURLOPT_URL               => $url,    // Prepared, set by request object.
            CURLOPT_HEADER            => false,   // Made by header function.
            CURLOPT_RETURNTRANSFER    => true,    // For properly parsing response headers & body.
            CURLINFO_HEADER_OUT       => true,    // For properly parsing request headers.
            // Mutable (client) options.
            CURLOPT_AUTOREFERER       => true,
            CURLOPT_FOLLOWLOCATION    => (bool) $clientOptions['redirs'],
            CURLOPT_MAXREDIRS         => (int)  $clientOptions['redirsMax'],
            CURLOPT_SSL_VERIFYHOST    => false,
            CURLOPT_SSL_VERIFYPEER    => false,
            CURLOPT_DEFAULT_PROTOCOL  => 'http',
            CURLOPT_DNS_CACHE_TIMEOUT => 3600, // 1 hour.
            CURLOPT_TIMEOUT           => (int) $clientOptions['timeout'],
            CURLOPT_CONNECTTIMEOUT    => (int) $clientOptions['timeoutConnect'],
            CURLOPT_HEADERFUNCTION    => fn($_, $header) => $this->collectHeaders($header),
        ];

        // Request headers.
        $options[CURLOPT_HTTPHEADER][] = 'Expect:';
        foreach ($headers as $name => $value) {
            $options[CURLOPT_HTTPHEADER][] = $name .': '. $value;
        }

        // If body provided, Content-Type & Content-Length added automatically by cURL.
        // Else we add them manually, if method is suitable for this.
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } elseif (equals($method, 'POST', 'PUT', 'PATCH')) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Length: '. strlen((string) $body);
        }

        // Extra cURL options from client options.curl field.
        $optionsExtra = null;

        if (isset($clientOptions['curl'])) {
            is_array($clientOptions['curl']) || throw new CurlException(
                'Options "curl" field must be array|null, %t given', $clientOptions['curl']
            );
            $optionsExtra = $clientOptions['curl'];
        }

        // Somehow HEAD method is freezing requests and causing timeouts. @override
        if ($method === 'HEAD') {
            $optionsExtra[CURLOPT_NOBODY] = true;
        } elseif ($method === 'POST') {
            $optionsExtra[CURLOPT_POST] = true;
        }

        // Add "userpass" stuff for basic authorizations.
        if (isset($clientOptions['userpass'])) {
            $optionsExtra[CURLOPT_USERPWD] = is_array($clientOptions['userpass'])
                ? join(':', $clientOptions['userpass']) : (string) $clientOptions['userpass'];
        }

        // Assign HTTP version if provided.
        if (isset($clientOptions['httpVersion'])) {
            $httpVersion = format_number($clientOptions['httpVersion'], 1);
            $optionsExtra[CURLOPT_HTTP_VERSION] = match ($httpVersion) {
                '2.0'   => CURL_HTTP_VERSION_2_0,
                '1.1'   => CURL_HTTP_VERSION_1_1,
                '1.0'   => CURL_HTTP_VERSION_1_0,
                default => throw new CurlException(
                    'Invalid "httpVersion" option %q [valids: 2, 2.0, 1.1, 1.0]',
                    $clientOptions['httpVersion']
                )
            };
        }

        if ($optionsExtra !== null) {
            // // HTTP/2 requires a https scheme.
            // if (isset($optionsExtra[CURLOPT_HTTP_VERSION])
            //     && $optionsExtra[CURLOPT_HTTP_VERSION] === CURL_HTTP_VERSION_2_0
            //     && !str_starts_with($url, 'https')) {
            //     throw new CurlException('URL scheme must be "https" for HTTP/2 requests');
            // }

            foreach ($optionsExtra as $option => $value) {
                // Check constant option.
                if (!$option || !is_int($option)) {
                    throw new CurlException('Invalid cURL constant %q', $option);
                }

                // Check for internal options.
                if ($this->checkOption($option, $name)) {
                    throw new CurlException(
                        'Blocked cURL option %s given [blocked options: %A]',
                        [$name, array_keys(self::BLOCKED_OPTIONS)]
                    );
                }

                if (is_array($value)) {
                    foreach ($value as $value) {
                        $options[$option][] = $value;
                    }
                } else {
                    $options[$option] = $value;
                }
            }
        }

        if (!curl_setopt_array($handle, $options)) {
            throw new CurlException('Failed to apply cURL options [error: @error]');
        }

        return $handle;
    }

    /**
     * Collect response headers (used for CURLOPT_HEADERFUNCTION option).
     * @since 5.0
     */
    private function collectHeaders(string $header): int
    {
        $line = trim($header);

        if ($line !== '') {
            // Status lines (for separating headers of redirect/continue etc.).
            if (str_starts_with($line, 'HTTP/')) {
                $this->headers .= "\r\n";
            }

            $this->headers .= $line . "\r\n";
        }

        return strlen($header);
    }

    /**
     * Check option validity.
     */
    private function checkOption(mixed $option, string|null &$name): bool
    {
        $name = null;

        foreach (self::BLOCKED_OPTIONS as $_name => $_option) {
            if ($option === $_option) {
                $name = $_name;
                break;
            }
        }

        return ($name !== null);
    }
}
