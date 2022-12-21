<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\message;

use froq\common\object\Enum;

/**
 * @package froq\http\message
 * @class   froq\http\message\ContentType
 * @author  Kerem Güneş
 * @since   5.0
 * @enum
 */
class ContentType extends Enum
{
    /** Not assigned. */
    public const NA = 'n/a';

    /** Texts. */
    public const TEXT_HTML = 'text/html', TEXT_PLAIN = 'text/plain',
                 TEXT_XML = 'text/xml', TEXT_JSON = 'text/json',
                 APPLICATION_XML = 'application/xml',
                 APPLICATION_JSON = 'application/json';

    /** Images. */
    public const IMAGE_JPEG = 'image/jpeg', IMAGE_WEBP = 'image/webp',
                 IMAGE_PNG = 'image/png', IMAGE_GIF = 'image/gif';

    /** Files (download). */
    public const APPLICATION_OCTET_STREAM = 'application/octet-stream',
                 APPLICATION_DOWNLOAD = 'application/download';
}
