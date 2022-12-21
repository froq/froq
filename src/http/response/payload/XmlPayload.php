<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response\payload;

use froq\http\{Response, message\ContentType};
use froq\encoding\encoder\XmlEncoder;

/**
 * Payload class for sending XML texts as response content.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\XmlPayload
 * @author  Kerem Güneş
 * @since   4.0
 */
class XmlPayload extends Payload implements PayloadInterface
{
    /**
     * Constructor.
     *
     * @param int          $code
     * @param array|string $content
     * @param array|null   $attributes
     * @param froq\http\Response|null @internal
     */
    public function __construct(int $code, array|string $content, array $attributes = null, Response $response = null)
    {
        $attributes['type'] ??= ContentType::APPLICATION_XML;

        parent::__construct($code, $content, $attributes, $response);
    }

    /**
     * @inheritDoc froq\http\response\payload\PayloadInterface
     */
    public function handle(): string
    {
        $content = $this->getContent();

        if (!is_array($content) && !is_string($content)) {
            throw new PayloadException(
                'Content must be array|string for XML payloads, %t given',
                $content
            );
        }

        if (!is_string($content) && !XmlEncoder::isEncoded($content)) {
            // When given in config as "response.xml" field.
            $options = (array) $this->response?->app->config('response.xml');

            $encoder = new XmlEncoder($options);
            $encoder->setInput($content);

            if ($encoder->encode()) {
                $content = $encoder->getOutput();
            } elseif ($error = $encoder->error()) {
                throw new PayloadException($error);
            }
        }

        return $content;
    }
}
