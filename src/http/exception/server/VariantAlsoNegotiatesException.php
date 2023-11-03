<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\exception\server;

use froq\http\exception\ServerException;
use froq\http\response\Status;

/**
 * @package froq\http\exception\server
 * @class   froq\http\exception\server\VariantAlsoNegotiatesException
 * @author  Kerem Güneş
 * @since   5.0
 */
class VariantAlsoNegotiatesException extends ServerException
{
    /** Code as status code. */
    public final const CODE = Status::VARIANT_ALSO_NEGOTIATES;
}
