<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response\payload;

use froq\http\{Response, message\ContentType};
use froq\common\trait\AttributeTrait;
use froq\file\mime\Mime;

/**
 * Base payload class.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\Payload
 * @author  Kerem Güneş
 * @since   4.0
 */
class Payload
{
    use AttributeTrait;

    /** Payload content. */
    protected mixed $content;

    /** Response instance. */
    protected Response|null $response;

    /**
     * Constructor.
     *
     * @param int        $code
     * @param mixed|null $content
     * @param array|null $attributes
     * @param froq\http\Response|null @internal
     */
    public function __construct(int $code, mixed $content = null, array $attributes = null, Response $response = null)
    {
        $this->content      = $content;
        $this->response     = $response;

        $attributes['code'] = $code;

        $this->setAttributes($attributes);
    }

    /**
     * Get content.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Get content type.
     *
     * @return string|null
     * @since  6.0
     */
    public function getContentType(): string|null
    {
        return $this->getAttribute('type');
    }

    /**
     * Set owner response.
     *
     * @param  froq\http\Response $response
     * @return void
     * @internal
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * Get owner response.
     *
     * @return froq\http\Response|null
     * @internal
     */
    public function getResponse(): Response|null
    {
        return $this->response;
    }

    /**
     * Get response code attribute.
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->getAttribute('code');
    }

    /**
     * Get response headers attribute.
     *
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->getAttribute('headers', []);
    }

    /**
     * Get response cookies attribute.
     *
     * @return array
     */
    public function getResponseCookies(): array
    {
        return $this->getAttribute('cookies', []);
    }

    /**
     * Detect payload content type, processes over and return an array which contains content,
     * content attributes (mime, size or filename etc.) and response attributes (code, headers,
     * cookies).
     *
     * @param  froq\http\Response $response
     * @return array
     * @throws froq\http\response\payload\PayloadException
     */
    public function process(Response $response): array
    {
        $payload = $this;
        $payload->setResponse($response);

        // Check non-body stuff.
        if (!$response->allowsBody()) {
            return [
                // Content.
                null,
                // Content attributes.
                $payload->getAttributes(),
                // Response attributes.
                [$payload->getResponseCode(),
                 $payload->getResponseHeaders(),
                 $payload->getResponseCookies()]
            ];
        }

        // Ready to handle (eg: JsonPayload etc).
        if ($payload instanceof PayloadInterface) {
            $content = $payload->handle();
        } else {
            $contentType = (string) $payload->getContentType();

            // Detect content type and process.
            switch ($type = $this->sniffContentType($contentType)) {
                case ContentType::NA:
                    $content = null;
                    break;
                case 'text':
                    $content = $payload->content;
                    break;
                case 'json': case 'xml':
                case 'image': case 'file': case 'download':
                    $payload = $this->createPayload($type, [
                        $payload->getResponseCode(), $payload->getContent(),
                        $payload->getAttributes(), $response
                    ]);
                    $content = $payload->handle();
                    break;
                default:
                    throw new PayloadException('Invalid content type %q', $type ?? $contentType);
            }
        }

        return [
            // Content.
            $content,
            // Content attributes.
            $payload->getAttributes(),
            // Response attributes.
            [$payload->getResponseCode(),
             $payload->getResponseHeaders(),
             $payload->getResponseCookies()]
        ];
    }

    /**
     * Sniff given content type and return a pseudo type if valid.
     */
    private function sniffContentType(string $contentType): string|null
    {
        $contentType = strtolower($contentType);
        if ($contentType === ContentType::NA) {
            return $contentType;
        }

        // Eg: text/html, image/jpeg, application/json, foo/download.
        if (preg_match('~^(\w+)/(?:.*?(\w+)$)?~', $contentType, $match)) {
            $match = match ($match[2]) {
                // JSON & XML types.
                'json' => 'json', 'xml' => 'xml',
                // Known text types.
                'html', 'plain', 'css', 'javascript' => 'text',
                // Known image types.
                'jpeg', 'webp', 'png', 'gif' => 'image',
                // File downloads.
                'octet-stream', 'download' => 'file',
                // Other text types.
                default => $match[1] === 'text' ? 'text' : null,
            };

            // Any matches above.
            if ($match) {
                return $match;
            }

            // Any extension with a valid type.
            if (Mime::getExtensionByType($contentType)) {
                return 'download';
            }
        }

        // Invalid.
        return null;
    }

    /**
     * Create a payload object by given pseudo type.
     */
    private function createPayload(string $type, array $args): PayloadInterface
    {
        switch ($type) {
            case 'json':
                return new JsonPayload(...$args);
            case 'xml':
                return new XmlPayload(...$args);
            case 'image':
                return new ImagePayload(...$args);
            case 'file':
            case 'download':
                return new FilePayload(...$args);
        };
    }
}
