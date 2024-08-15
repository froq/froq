<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client;

use froq\util\mapper\Mapper;
use JsonError;

/**
 * A server response class.
 *
 * @package froq\http\client
 * @class   froq\http\client\Response
 * @author  Kerem Güneş
 * @since   3.0
 */
class Response extends Message
{
    /** Status. */
    private int $status;

    /**
     * Constructor.
     *
     * @param int         $status
     * @param string|null $body
     * @param array|null  $headers
     */
    public function __construct(int $status, string $body = null, array $headers = null)
    {
        $this->setStatus($status);

        parent::__construct(null, $headers, $body);
    }

    /**
     * Set status.
     *
     * @param  int $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get body, decode if GZip'ed as default.
     *
     * @param  bool $decode
     * @return string|null
     * @throws Error
     * @override
     */
    public function getBody(bool $decode = true): string|null
    {
        if ($this->body && $decode) {
            $contentEncoding = $this->getHeader('content-encoding', '');

            if (str_contains($contentEncoding, 'gzip')) {
                $decodedBody = @gzdecode($this->body);

                if ($decodedBody === false) {
                    $error = error_message($code);
                    throw new \Error($error, $code);
                }

                return $decodedBody;
            }
        }

        return $this->body;
    }

    /**
     * Get decoded body (decode if GZip'ed).
     *
     * @return string|null
     */
    public function getDecodedBody(): string|null
    {
        return $this->getBody(decode: true);
    }

    /**
     * Get parsed body (parse if JSON'ed).
     *
     * @param  bool           $array
     * @param  JsonError|null &$error
     * @return mixed|null
     */
    public function getParsedBody(bool $array = true, JsonError &$error = null): mixed
    {
        $decodedBody = (string) $this->getDecodedBody();

        return json_unserialize($decodedBody, $array, $error);
    }

    /**
     * Get parsed body mapping to given object.
     *
     * @param  object $object
     * @param  array  $options
     * @param  bool   $validate
     * @return object|null
     */
    public function getMappedBody(object $object, array $options = [], bool $validate = true): object|null
    {
        $parsedBody = (array) $this->getParsedBody();

        if ($validate && $parsedBody === []) {
            return null;
        }

        return (new Mapper($object, $options))->map($parsedBody);
    }
}
