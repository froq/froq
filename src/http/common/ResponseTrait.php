<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\common;

/**
 * A trait, provides some utilities for response class.
 *
 * @package froq\http\common
 * @class   froq\http\common\ResponseTrait
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
trait ResponseTrait
{
    use HeaderTrait, CookieTrait;

    /**
     * Set status code.
     *
     * @param  int $code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->status->setCode($code);

        return $this;
    }

    /**
     * Get status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status->getCode();
    }

    /**
     * Set content.
     *
     * @param  mixed       $content
     * @param  string|null $type
     * @param  string|null $charset
     * @return self
     */
    public function setContent(mixed $content, string $type = null, string $charset = null): self
    {
        $this->body->setContent($content);

        $type    && $this->body->setContentType($type);
        $charset && $this->body->setContentCharset($charset);

        return $this;
    }

    /**
     * Get content.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->body->getContent();
    }

    /**
     * Set content type.
     *
     * @param  string $type
     * @return self
     */
    public function setContentType(string $type): self
    {
        $this->body->setContentType($type);

        return $this;
    }

    /**
     * Get content type.
     *
     * @return string|null
     */
    public function getContentType(): string|null
    {
        return $this->body->getContentType();
    }

    /**
     * Set content charset.
     *
     * @param  string $charset
     * @return self
     */
    public function setContentCharset(string $charset): self
    {
        $this->body->setContentCharset($charset);

        return $this;
    }

    /**
     * Get content charset.
     *
     * @return string|null
     */
    public function getContentCharset(): string|null
    {
        return $this->body->getContentCharset();
    }

    /**
     * Set content attributes.
     *
     * @param  string $attributes
     * @return self
     */
    public function setContentAttributes(array $attributes): self
    {
        $this->body->setAttributes($attributes);

        return $this;
    }

    /**
     * Get content attributes.
     *
     * @return array|null
     */
    public function getContentAttributes(): array|null
    {
        return $this->body->getAttributes() ?: null;
    }

    /**
     * Response allows a body?
     *
     * @return bool
     */
    public function allowsBody(): bool
    {
        return $this->status->isAllowedForBody();
    }
}
