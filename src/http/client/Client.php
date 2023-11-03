<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client;

use froq\http\client\curl\{Curl, CurlError, CurlResponseError};
use froq\common\trait\OptionTrait;
use froq\event\EventManager;

/**
 * A client class that interacts via cURL library with the remote servers using only HTTP protocols.
 * Hence it should not be used for other protocols and should be ensure that cURL library is available.
 *
 * @package froq\http\client
 * @class   froq\http\client\Client
 * @author  Kerem Güneş
 * @since   3.0
 */
class Client
{
    use OptionTrait;

    /** Request instance. */
    private Request $request;

    /** Response instance. */
    private Response $response;

    /** Curl instance. */
    private Curl $curl;

    /** Curl or Curl response error. */
    private CurlError|CurlResponseError|null $error = null;

    /** Result. */
    private ?string $result = null;

    /** Result info. */
    private ?array $resultInfo = null;

    /** Default options. */
    private static array $optionsDefault = [
        'redirs'      => true,  'redirsMax'       => 3,
        'timeout'     => 5,     'timeoutConnect'  => 3,
        'keepResult'  => true,  'keepResultInfo'  => true,
        'throwErrors' => false, 'throwHttpErrors' => false,
        'httpVersion' => null,  'userpass'        => null,
        'gzip'        => true,  'json'            => false,
        'method'      => 'GET', 'curl'            => null, // Curl options.
    ];

    /** Event manager instance. */
    private EventManager $eventManager;

    /** Sent state. */
    public bool $sent = false;

    /** Abort state. */
    public bool $abort = false;

    /**
     * Constructor.
     *
     * @param string|null                  $url
     * @param array<string, mixed>|null    $options
     * @param array<string, callable>|null $events
     */
    public function __construct(string $url = null, array $options = null, array $events = null)
    {
        // Just as a syntactic sugar, URL is a parameter.
        $options = ['url' => $url] + ($options ?? []);

        $this->setOptions($options, self::$optionsDefault);

        $this->eventManager = new EventManager($this);
        if ($events) foreach ($events as $name => $callback) {
            $this->eventManager->add($name, $callback);
        }
    }

    /**
     * Set curl object created by Sender class.
     *
     * @param  froq\http\client\curl\Curl
     * @return self
     * @internal
     */
    public function setCurl(Curl $curl): self
    {
        $this->curl = $curl;

        return $this;
    }

    /**
     * Get curl object created by Sender class.
     *
     * Note: This method should not be called before send calls, a `ClientException` will be thrown
     * otherwise.
     *
     * @return froq\http\client\curl\Curl
     * @throws froq\http\client\ClientException
     */
    public function getCurl(): Curl
    {
        $this->sent || throw new ClientException(
            'Cannot access $curl property before send calls'
        );

        return $this->curl;
    }

    /**
     * Get error if any failure was occured while cURL execution.
     *
     * Note: This method should not be called before send calls, a `ClientException` will be thrown
     * otherwise.
     *
     * @return froq\http\client\curl\{CurlError|CurlResponseError}
     * @throws froq\http\client\ClientException
     */
    public function getError(): CurlError|CurlResponseError|null
    {
        $this->sent || throw new ClientException(
            'Cannot access $error property before send calls'
        );

        return $this->error;
    }

    /**
     * Get request property that set after send calls.
     *
     * Note: This method should not be called before send calls, a `ClientException` will be thrown
     * otherwise.
     *
     * @return froq\http\client\Request
     * @throws froq\http\client\ClientException
     */
    public function getRequest(): Request
    {
        $this->sent || throw new ClientException(
            'Cannot access $request property before send calls'
        );

        return $this->request;
    }

    /**
     * Get response property that set after send calls.
     *
     * Note: This method should not be called before send calls, a `ClientException` will be thrown
     * otherwise.
     *
     * @return froq\http\client\Response
     * @throws froq\http\client\ClientException
     */
    public function getResponse(): Response
    {
        $this->sent || throw new ClientException(
            'Cannot access $response property before send calls'
        );

        return $this->response;
    }

    /**
     * Get result property that set after send calls.
     *
     * Note: If any error occurs after calls or `options.keepResult` is false returns null.
     *
     * @return string|null
     */
    public function getResult(): string|null
    {
        return $this->result;
    }

    /**
     * Get result info property that set after send calls.
     *
     * Note: If any error occurs after calls or `options.keepResultInfo` is false returns null.
     *
     * @return array|null
     */
    public function getResultInfo(): array|null
    {
        return $this->resultInfo;
    }

    /**
     * Send a "HEAD" request.
     *
     * @see send()
     * @since 5.0
     */
    public function head(...$args)
    {
        return $this->send('HEAD', ...$args);
    }

    /**
     * Send a "GET" request.
     *
     * @see send()
     * @since 5.0
     */
    public function get(...$args)
    {
        return $this->send('GET', ...$args);
    }

    /**
     * Send a "POST" request.
     *
     * @see send()
     * @since 5.0
     */
    public function post(...$args)
    {
        return $this->send('POST', ...$args);
    }

    /**
     * Send a "PUT" request.
     *
     * @see send()
     * @since 5.0
     */
    public function put(...$args)
    {
        return $this->send('PUT', ...$args);
    }

    /**
     * Send a "DELETE" request.
     *
     * @see send()
     * @since 5.0
     */
    public function delete(...$args)
    {
        return $this->send('DELETE', ...$args);
    }

    /**
     * Send a request with given arguments. This method is a shortcut method for operations such
     * send-a-request then get-a-response.
     *
     * @param  string|null $method
     * @param  string|null $url
     * @param  array|null  $urlParams
     * @param  string|null $body
     * @param  array|null  $headers
     * @param  array|null  $query  Alias for $urlParams
     * @return froq\http\client\Response
     */
    public function send(string $method = null, string $url = null, array $urlParams = null,
        string|array $body = null, array $headers = null, array $query = null): Response
    {
        // May be set via setOption().
        $method    = $method ?: $this->getOption('method');
        $url       = $url    ?: $this->getOption('url');
        $urlParams = array_replace_recursive($this->getOption('urlParams', []), $urlParams ?: $query ?: []);
        $body      = $body   ?: $this->getOption('body');
        $headers   = array_replace_recursive($this->getOption('headers', []), $headers ?: []);

        $this->setOptions(['method' => $method, 'url' => $url, 'urlParams' => $urlParams, 'body' => $body,
            'headers' => $headers]);

        return Sender::send($this);
    }

    /**
     * Setup is an internal method and called by `Curl` and `CurlMulti` before cURL operations starts
     * in `run()` method, for both single and multi clients. Throws a `ClientException` if no method,
     * no URL or an invalid URL given.
     *
     * @return void
     * @throws froq\http\client\ClientException
     * @internal
     */
    public function setup(): void
    {
        [$method, $url, $urlParams, $body, $headers, $cookies] = $this->getOptions(
            ['method', 'url', 'urlParams', 'body', 'headers', 'cookies']
        );

        $method || throw new ClientException('No method given');
        $url    || throw new ClientException('No URL given');

        // Reproduce URL structure.
        $parsedUrl = http_parse_url($url);
        if (!$parsedUrl) {
            throw new ClientException('Invalid URL %q', $url);
        }

        // Ensure scheme is http or https.
        if (!in_array($parsedUrl['scheme'], ['http', 'https'], true)) {
            throw new ClientException('Invalid URL scheme %q [valids: %A]', $url, ['http', 'https']);
        }

        // Update params if given.
        if ($urlParams) {
            $urlParams = array_replace_recursive(
                (array) $parsedUrl['queryParams'], (array) $urlParams
            );
            $parsedUrl['queryParams'] = $urlParams;
        }

        $url = http_build_url($parsedUrl);

        $headers = array_lower_keys((array) $headers);

        // Add cookies (if provided).
        if ($cookies) {
            $cookies = array_reduce_keys((array) $cookies, [], fn($ret, $name): array => (
                [...$ret, join('=', [$name, $cookies[$name]])]
            ));

            $headers['cookie'] = join('; ', $cookies);
        }

        // Disable GZip'ed responses.
        if (!$this->options['gzip']) {
            $headers['accept-encoding'] = null;
        }

        $contentType = null;
        if (isset($headers['content-type'])) {
            $contentType = $headers['content-type'] = strtolower($headers['content-type']);
        }

        // Add JSON header if options json is true.
        if ($this->options['json'] && (!$contentType || !str_contains($contentType, 'json'))) {
            $contentType = $headers['content-type'] = 'application/json';
        }

        // Encode body & add related headers if needed.
        if ($body && is_array($body)) {
            if ($contentType && str_contains($contentType, 'json')) {
                $body = json_encode($body, flags: (
                    JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
                ));
            } else {
                $body = http_build_query($body);
                $contentType = 'application/x-www-form-urlencoded';
            }

            $headers['content-type']   ??= $contentType;
            $headers['content-length'] ??= (string) strlen($body);
        }

        // Create message objects.
        $this->request  = new Request($method, $url, $urlParams, $body, $headers);
        $this->response = new Response(0, null, null, null);
    }

    /**
     * End is an internal method and called by `Curl` and `CurlMulti` after cURL operations end
     * in `run()` method, for both single and multi clients.
     *
     * @param  string|null                     $result
     * @param  array|null                      $resultInfo
     * @param  froq\http\client\CurlError|null $error
     * @return void
     * @throws froq\http\client\curl\{CurlError|CurlResponseError}
     * @internal
     */
    public function end(string|null $result, array|null $resultInfo, CurlError $error = null): void
    {
        if ($result || $resultInfo) {
            $headers = http_parse_headers($resultInfo['request_header']);
            if (!$headers) {
                return;
            }

            if ($this->options['keepResult']) {
                $this->result = $result;
            }
            if ($this->options['keepResultInfo']) {
                $resultInfo += ['finalUrl'    => null, 'refererUrl'     => null,
                                'contentType' => null, 'contentCharset' => null];

                $resultInfo['finalUrl']   = $resultInfo['url'];
                $resultInfo['refererUrl'] = $headers['Referer'] ?? $headers['referers'] ?? null;

                if (isset($resultInfo['content_type'])) {
                    sscanf($resultInfo['content_type'], '%[^;];%[^=]=%[^$]', $contentType, $_, $contentCharset);

                    $resultInfo['contentType']    = $contentType;
                    $resultInfo['contentCharset'] = $contentCharset ? strtolower($contentCharset) : null;
                }

                $this->resultInfo = $resultInfo;
            }

            $requestLine = http_parse_request_line($headers[0]);
            if (!$requestLine) {
                return;
            }

            // Update request object details.
            $this->request->setHttpProtocol($requestLine['protocol'])
                          ->setHttpVersion($requestLine['version'])
                          ->setHeaders($headers, reset: true);

            // @cancel: Using CURLOPT_HEADERFUNCTION option in Curl object.
            // Checker for redirections etc. (for finding final HTTP-Message).
            // $next = fn($body): bool => $body && str_starts_with($body, 'HTTP/');

            // @[$headers, $body] = explode("\r\n\r\n", $result, 2);
            // if ($next($body)) {
            //     do {
            //         @[$headers, $body] = explode("\r\n\r\n", $body, 2);
            //     } while ($next($body));
            // }

            $headers = $resultInfo['response_header'];

            // Get last slice of multi headers (eg: redirections).
            if ($headers && str_contains($headers, "\r\n\r\n")) {
                $headers = last(explode("\r\n\r\n", $headers));
            }

            $headers = http_parse_headers($headers);
            if (!$headers) {
                return;
            }

            $responseLine = http_parse_response_line($headers[0]);
            if (!$responseLine) {
                return;
            }

            // Update response object details.
            $this->response->setHttpProtocol($responseLine['protocol'])
                           ->setHttpVersion($responseLine['version'])
                           ->setStatus($responseLine['status'])
                           ->setHeaders($headers);

            // Set response raw & parsed body.
            if ((string) $result !== '') {
                $body = $result;
                $parsedBody = null;
                unset($result);

                $contentEncoding = $this->response->getHeader('content-encoding');
                $contentType = $this->response->getHeader('content-type');

                // Decode GZip (if GZip'ed).
                if ($contentEncoding && str_contains($contentEncoding, 'gzip')) {
                    $decodedBody = gzdecode($body);
                    if (is_string($decodedBody)) {
                        $body = $decodedBody;
                    }
                    unset($decodedBody);
                }

                // Decode JSON (if JSON'ed).
                if ($contentType && str_contains($contentType, 'json')) {
                    $decodedBody = json_decode($body, flags: JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING);
                    if (is_array($decodedBody)) {
                        $parsedBody = $decodedBody;
                    }
                    unset($decodedBody);
                }

                $this->response->setBody($body);
                $this->response->setParsedBody($parsedBody);
            }
        }

        // These options may discard error event below.
        if ($error) {
            $this->error = $error;

            if ($this->options['throwErrors']) {
                throw $this->error;
            }
        } elseif (!$error && $resultInfo['http_code'] >= 400) {
            $this->error = new CurlResponseError($resultInfo['http_code']);
            $this->error->setRequest(clone $this->request);
            $this->error->setResponse(clone $this->response);

            if ($this->options['throwHttpErrors']) {
                throw $this->error;
            }
        }

        // Call error event if exists.
        if ($this->error) {
            $this->fireEvent('error', $this->error);
        }

        // Call end event if exists.
        $this->fireEvent('end');
    }

    /**
     * Fire an event that was set in options. The only names that called are limited to: "end",
     * "error" and "abort".
     * - end: always fired when the cURL execution and request finish.
     * - error: fired when a cURL error occurs.
     * - abort: fired when an abort operation occurs. To achieve this, so break client queue, a
     * callback must be defined in for breaker client and set client `$abort` property as true in
     * that callback.
     *
     * @param  string $name
     * @return void
     * @since  4.0
     * @internal
     */
    public function fireEvent(string $name, mixed ...$arguments): void
    {
        if ($this->eventManager->has($name)) {
            $this->eventManager->fire($name, ...$arguments);
        }
    }
}
