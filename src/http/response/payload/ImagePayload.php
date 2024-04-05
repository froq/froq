<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response\payload;

use froq\http\{Response, message\ContentType};
use froq\file\{Image, ImageException};

/**
 * Payload class for sending images as response content.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\ImagePayload
 * @author  Kerem Güneş
 * @since   3.9
 */
class ImagePayload extends Payload implements PayloadInterface
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
        parent::__construct($code, $content, $attributes, $response);
    }

    /**
     * @inheritDoc froq\http\response\payload\PayloadInterface
     */
    public function handle(): Image
    {
        $image = $this->getContent();

        [$imageType, $imageSize, $modifiedAt]
            = $this->getAttributes(['type', 'size', 'modifiedAt']);

        if (!$image) {
            throw new PayloadException('Image empty');
        } elseif (!is_string($image)) {
            throw new PayloadException('Image content must be a valid readable file path '.
                'or source file data, %t given', $image);
        } elseif (!isset($imageType) || !$this->isValidImageType($imageType)) {
            throw new PayloadException('Image type must be string and a valid image MIME type');
        }

        try {
            // A regular file.
            if (@is_file($image)) {
                $image = new Image($image);
                $image->open('rb');
            } else {
                // Or contents of file.
                $image = Image::fromString($image);
            }

            $imageType && $image->setMime($imageType);
        } catch (FileException $e) {
            throw new PayloadException($e, cause: $e->getCause());
        }

        $imageSize  = $imageSize  ?: $image->size();
        $modifiedAt = $modifiedAt ?: $image->stat()['mtime'];

        // Update attributes.
        $this->setAttributes([
            'size' => $imageSize, 'modifiedAt' => $modifiedAt,
            // 'temp' => !!$this->getAttribute('temp')
        ]);

        return $image;
    }

    /**
     * Valid image type checker.
     */
    private function isValidImageType(mixed $imageType): bool
    {
        return is_string($imageType) && preg_test('~^image/([a-z\-\.]+)$~', $imageType);
    }
}
