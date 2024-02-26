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
        return strtolower((string) $this->getContentType()) === ContentType::NA;
    }

    /**
     * Is text.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return str_has_prefix((string) $this->getContentType(), 'text/', true)
            || str_has((string) $this->getContentType(), ['/json', '/xml'], true);
    }

    /**
     * Is image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_has_prefix((string) $this->getContentType(), 'image/', true);
    }

    /**
     * Is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return str_has((string) $this->getContentType(), '/json', true);
    }

    /**
     * Is XML.
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return str_has((string) $this->getContentType(), '/xml', true);
    }

    /**
     * Is file.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return str_has_suffix((string) $this->getContentType(), ['octet-stream', 'download'], true);
    }
}
