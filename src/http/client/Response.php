<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client;

use froq\util\mapper\Mapper;

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

    /** Parsed body (for JSON stuff). */
    private ?array $parsedBody = null;

    /**
     * Constructor.
     *
     * @param int         $status
     * @param string|null $body
     * @param array|null  $parsedBody
     * @param array|null  $headers
     */
    public function __construct(int $status = 0, string $body = null, array $parsedBody = null,
        array $headers = null)
    {
        $this->setStatus($status)
             ->setParsedBody($parsedBody);

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
     * Set parsed body.
     *
     * @param  array|null $parsedBody
     * @return self
     */
    public function setParsedBody(array|null $parsedBody): self
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }

    /**
     * Get parsed body.
     *
     * @return array|null
     */
    public function getParsedBody(): array|null
    {
        return $this->parsedBody;
    }

    /**
     * Get parsed body mapping to given object.
     *
     * @param  object $object
     * @param  array  $options
     * @param  bool   $skipNullBody
     * @return object|null
     */
    public function getMappedBody(object $object, array $options = [], bool $skipNullBody = true): object|null
    {
        if ($skipNullBody && $this->parsedBody === null) {
            return null;
        }

        $mapper = new Mapper($object, $options);
        return $mapper->map((array) $this->parsedBody);
    }
}
