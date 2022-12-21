<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response\payload;

use froq\http\{Response, message\ContentType};

/**
 * Payload class for sending plain texts as response content.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\PlainPayload
 * @author  Kerem Güneş
 * @since   3.0
 */
class PlainPayload extends Payload implements PayloadInterface
{
    /**
     * Constructor.
     *
     * @param int        $code
     * @param string     $content
     * @param array|null $attributes
     * @param froq\http\Response|null @internal
     */
    public function __construct(int $code, string $content, array $attributes = null, Response $response = null)
    {
        $attributes['type'] = ContentType::TEXT_PLAIN;

        parent::__construct($code, $content, $attributes, $response);
    }

    /**
     * @inheritDoc froq\http\response\payload\PayloadInterface
     */
    public function handle(): string
    {
        return (string) $this->getContent();
    }
}
