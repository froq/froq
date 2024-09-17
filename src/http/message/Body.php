<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\message;

use froq\common\trait\AttributeTrait;

/**
 * @package froq\http\message
 * @class   froq\http\message\Body
 * @author  Kerem Güneş
 * @since   1.0
 */
class Body
{
    use AttributeTrait;

    /** Content. */
    private mixed $content;

    /**
     * Constructor.
     *
     * @param mixed|null $content
     * @param array|null $attributes
     */
    public function __construct(mixed $content = null, array $attributes = null)
    {
        $this->content    = $content;
        $this->attributes = $attributes ?? [];
    }

    /**
     * Set content.
     *
     * @param  mixed $content
     * @return self
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
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
     * Set content type.
     *
     * @param  string $type
     * @return self
     */
    public function setContentType(string $type): self
    {
        $this->setAttribute('type', $type);

        return $this;
    }

    /**
     * Get content type.
     *
     * @return string|null
     */
    public function getContentType(): string|null
    {
        return $this->getAttribute('type');
    }

    /**
     * Set content charset.
     *
     * @param  string $charset
     * @return self
     */
    public function setContentCharset(string $charset): self
    {
        $this->setAttribute('charset', $charset);

        return $this;
    }

    /**
     * Get content charset.
     *
     * @return string|null
     */
    public function getContentCharset(): string|null
    {
        return $this->getAttribute('charset');
    }

    /**
     * Is na.
     *
     * @return bool
     */
    public function isNa(): bool
    {
        return $this->getContentType() === ContentType::NA;
    }

    /**
     * Is text.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return mime_check_type(
            (string) $this->getContentType(),
            '~^text/|[/+](json|xml)$~'
        );
    }

    /**
     * Is image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return mime_check_type(
            (string) $this->getContentType(),
            '~^image/~'
        );
    }

    /**
     * Is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return mime_check_type(
            (string) $this->getContentType(),
            '~[/+]json$~'
        );
    }

    /**
     * Is XML.
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return mime_check_type(
            (string) $this->getContentType(),
            '~[/+]xml$~'
        );
    }

    /**
     * Is file.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return mime_check_type(
            (string) $this->getContentType(),
            '~/(octet-stream|download)$~'
        );
    }
}
