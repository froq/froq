<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http;

use froq\http\common\RequestTrait;
use froq\http\request\{Method, Scheme, Uri, Client, Params, Files, Segments};
use froq\http\request\payload\{FormPayload, JsonPayload, FilePayload, FilesPayload,
    UploadedFile, UploadedFiles};
use froq\{App, util\Util};
use UrlQuery;

/**
 * A HTTP request class, extends `Message` class and mainly deals with Froq! application
 * and controllers.
 *
 * @package froq\http
 * @class   froq\http\Request
 * @author  Kerem Güneş
 * @since   1.0
 */
class Request extends Message
{
    use RequestTrait;

    /** Method instance. */
    public readonly Method $method;

    /** Scheme instance. */
    public readonly Scheme $scheme;

    /** Uri instance. */
    public readonly Uri $uri;

    /** Client instance. */
    public readonly Client $client;

    /** Request ID. */
    public readonly string $id;

    /** Request time. */
    public readonly int $time;

    /** Request micro time. */
    public readonly float $utime;

    /**
     * Constructor.
     *
     * @param froq\App
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->method = new Method($_SERVER['REQUEST_METHOD']);
        $this->scheme = new Scheme($_SERVER['REQUEST_SCHEME']);
        $this->uri    = new Uri(Util::getCurrentUrl());
        $this->client = new Client();

        $this->id     = get_request_id();
        $this->time   = $_SERVER['REQUEST_TIME'];
        $this->utime  = $_SERVER['REQUEST_TIME_FLOAT'];

        // Add path as a quick URI path info (alt: $_SERVER['PATH_INFO']).
        $_SERVER['REQUEST_PATH'] = current(explode('?', $_SERVER['REQUEST_URI'], 2));
    }

    /**
     * Get all params as GPC sort.
     *
     * @return array
     */
    public function params(): array
    {
        return Params::all();
    }

    /**
     * Get all uploaded files.
     *
     * @param  bool $payload
     * @return array|froq\http\request\payload\UploadedFiles
     */
    public function files(bool $payload = true): array|UploadedFiles
    {
        return $payload ? new UploadedFiles(Files::all()) : Files::all();
    }

    /**
     * Get an uploaded file.
     *
     * @param  string $id
     * @param  bool   $payload
     * @return array|froq\http\request\payload\UploadedFile|null
     */
    public function file(string $id, bool $payload = true): array|UploadedFile|null
    {
        foreach (Files::all() as $file) {
            if ($file['_id'] === $id) {
                return $payload ? UploadedFile::from($file) : $file;
            }
        }

        return null;
    }

    /**
     * Get query as immutable if present.
     *
     * @return UrlQuery|null
     */
    public function query(): UrlQuery|null
    {
        return ($query = $this->uri->getQuery()) ? clone $query : null;
    }

    /**
     * Get PHP input, optionally parse it.
     *
     * @param  bool $parse
     * @param  bool $json
     * @param  bool $assoc
     * @return mixed
     */
    public function input(bool $parse = false, bool $json = false, bool $assoc = true): mixed
    {
        $input = (string) file_get_contents('php://input');

        if ($parse) {
            $contentType = $this->getHeader('Content-Type', '');

            if ($json || str_contains($contentType, '/json')) {
                return json_unserialize($input, $assoc);
            }

            return http_parse_query($input);
        }

        return $input;
    }

    /**
     * Get PHP input as JSON-decoded.
     *
     * @param  bool $assoc
     * @return mixed
     */
    public function json(bool $assoc = true): mixed
    {
        return $this->input(true, true, $assoc);
    }

    /**
     * Get a URI segment.
     *
     * @param  int|string $key
     * @param  mixed|null $default
     * @return mixed
     */
    public function segment(int|string $key, mixed $default = null): mixed
    {
        return $this->uri->segment($key, $default);
    }

    /**
     * Get many URI segments.
     *
     * @param  array<int|string>|null $keys
     * @param  array|null             $defaults
     * @return array
     */
    public function segments(array $keys = null, array $defaults = null): array
    {
        return $this->uri->segments($keys, $defaults);
    }

    /**
     * Get method name.
     *
     * @return string
     * @since  4.7
     */
    public function getMethod(): string
    {
        return $this->method->getName();
    }

    /**
     * Get scheme name.
     *
     * @return string
     * @since  4.7
     */
    public function getScheme(): string
    {
        return $this->scheme->getName();
    }

    /**
     * Get URI.
     *
     * @param  bool $escape
     * @param  bool $withQuery
     * @return string
     * @since  4.7
     */
    public function getUri(bool $escape = false, bool $withQuery = true): string
    {
        $ret = '';

        if ($path = $this->uri->getPath()) {
            $ret .= !$escape ? $path : htmlspecialchars($path);
        }

        if ($withQuery && ($query = $this->uri->getQuery())) {
            $ret .= '?' . (!$escape ? $query : htmlspecialchars($query->toString()));
        }

        return $ret;
    }

    /**
     * Get URL.
     *
     * @param  bool $escape
     * @return string
     * @since  5.0
     */
    public function getUrl(bool $escape = false): string
    {
        return $this->uri->getOrigin() . $this->getUri($escape, true);
    }

    /**
     * Get path.
     *
     * @param  bool $escape
     * @return string
     * @since  6.0
     */
    public function getPath(bool $escape = false): string
    {
        return $this->getUri($escape, false);
    }

    /**
     * Get query.
     *
     * @return string|null
     * @since  6.0
     */
    public function getQuery(): string|null
    {
        return $_SERVER['QUERY_STRING'] ?? null;
    }

    /**
     * Get query param.
     *
     * @param  string      $name
     * @param  string|null $default
     * @return string|null
     * @since  7.0
     */
    public function getQueryParam(string $name, string $default = null): string|null
    {
        return array_select($_GET, $name, $default);
    }

    /**
     * Get query params.
     *
     * @param  array<string>      $names
     * @param  array<string>|null $defaults
     * @param  bool               $combine
     * @return array<string>|null
     * @since  7.0
     */
    public function getQueryParams(array $names = null, array $defaults = null, bool $combine = false): array|null
    {
        return ($names !== null) ? array_select($_GET, $names, $defaults, $combine) : $_GET;
    }

    /**
     * Get form payload.
     *
     * @return froq\http\request\payload\FormPayload
     */
    public function getFormPayload(): FormPayload
    {
        return new FormPayload($this);
    }

    /**
     * Get json payload.
     *
     * @return froq\http\request\payload\JsonPayload
     */
    public function getJsonPayload(): JsonPayload
    {
        return new JsonPayload($this);
    }

    /**
     * Get file payload.
     *
     * @return froq\http\request\payload\FilePayload
     */
    public function getFilePayload(): FilePayload
    {
        return new FilePayload($this);
    }

    /**
     * Get files payload.
     *
     * @return froq\http\request\payload\FilesPayload
     */
    public function getFilesPayload(): FilesPayload
    {
        return new FilesPayload($this);
    }

    /**
     * Get uploaded file.
     *
     * @return froq\http\request\payload\UploadedFile|null
     */
    public function getUploadedFile(): UploadedFile|null
    {
        if ($file = $this->getFilePayload()->file) {
            return $file->id ? $file : null;
        }

        return null;
    }

    /**
     * Get uploaded files.
     *
     * @return froq\http\request\payload\UploadedFiles|null
     */
    public function getUploadedFiles(): UploadedFiles|null
    {
        if ($files = $this->getFilesPayload()->files) {
            return new UploadedFiles(
                reduce($files, [],
                    function (array $ret, FilePayload $payload): array {
                        return concat($ret, $payload->file);
                    }
                )
            );
        }

        return null;
    }

    /**
     * Load request stuff (globals, headers, body etc.).
     *
     * @return void
     * @throws froq\http\RequestException
     * @internal Used by froq\App::run() method.
     */
    public function load(): void
    {
        static $done;

        // Check/tick for load-once state.
        $done ? throw new RequestException('Request was already loaded')
              : ($done = true);

        $headers = $this->prepareHeaders();

        [$contentType, $contentCharset]
            = $this->parseContentType($headers['Content-Type'] ?? '');

        // Set/parse body for overriding methods (PUT, DELETE or even for get).
        // Note: 'php://input' is not available with enctype="multipart/form-data".
        // @see https://www.php.net/manual/en/wrappers.php.php#wrappers.php.input.
        $content = str_contains($contentType, 'multipart/form-data') ? null : $this->input();

        $_GET = $this->prepareGlobals('GET');

        if ($content !== null) {
            // POST data always parsed, for GETs too (to utilize JSON payloads, thanks Elasticsearch).
            $_POST = $this->prepareGlobals('POST', $content, str_contains($contentType, '/json'));
        }

        $_COOKIE = $this->prepareGlobals('COOKIE');

        // Fill body (why keep content?).
        $this->setBody(null, ($contentType ? ['type' => $contentType, 'charset' => $contentCharset] : null));

        // Fill headers & cookies.
        foreach ($headers as $name => $value) {
            $this->headers->set($name, $value);
        }
        foreach ($_COOKIE as $name => $value) {
            $this->cookies->set($name, $value);
        }
    }

    /**
     * Normalize headers setting all keys title-cased (eg: "content-type" => "Content-Type").
     * @internal
     */
    private function prepareHeaders(): array
    {
        $headers = getallheaders();

        if (!$headers) {
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with((string) $key, 'HTTP_')) {
                    $name = str_replace(['_', ' '], '-', substr($key, 5));
                    $headers[$name] = $value;
                }
            }
        }

        // Title-case all names & reduce dashes.
        $headers = array_map_keys(fn($key): string => convert_case(
            (string) $key, CASE_TITLE, '-', reduce: '-'
        ), $headers);

        // Content issues.
        if (!isset($headers['Content-Type'])
            && isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (!isset($headers['Content-Length'])
            && isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (!isset($headers['Content-Md5'])
            && isset($_SERVER['CONTENT_MD5'])) {
            $headers['Content-Md5'] = $_SERVER['CONTENT_MD5'];
        }

        // Authorization issues.
        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $headers['Authorization'] = 'Basic ' . base64_encode(
                    $_SERVER['PHP_AUTH_USER'] .':'. ($_SERVER['PHP_AUTH_PW'] ?? '')
                );
            }
        }

        ksort($headers);

        return $headers;
    }

    /**
     * @internal
     */
    private function prepareGlobals(string $name, string $source = '', bool $json = false): array
    {
        // Plus check macro.
        $plussed = fn($s): bool => $s && str_contains($s, '+');

        switch ($name) {
            case 'GET':
                $source = (string) ($_SERVER['QUERY_STRING'] ?? '');

                if (!$plussed($source)) {
                    return $_GET;
                }
                break;
            case 'POST':
                if ($json) {
                    return (array) json_unserialize($source, true);
                }

                if (!$plussed($source)) {
                    return $_POST;
                }
                break;
            case 'COOKIE':
                $source = (string) ($_SERVER['HTTP_COOKIE'] ?? '');

                if (!$plussed($source)) {
                    return $_COOKIE;
                }

                if ($source !== '') {
                    $source = implode('&', array_map('trim', explode(';', $source)));
                }
                break;
        }

        // 'Cos http_build_query() uses PHP_QUERY_RFC1738, use same
        // here. Otherwise plus (+) signs won't be decoded properly.
        return http_parse_query($source, '&', PHP_QUERY_RFC1738);
    }

    /**
     * Parse content type.
     */
    private function parseContentType(string|null $contentType): array
    {
        $contentType = strtolower(trim($contentType ?? ''));

        if (preg_match('~(.+); +charset=(.+)~i', $contentType, $match)) {
            return [$match[1], $match[2]];
        }

        return [$contentType, null];
    }
}
