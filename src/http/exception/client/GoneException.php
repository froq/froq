<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\exception\client;

use froq\http\exception\ClientException;
use froq\http\response\Status;

/**
 * @package froq\http\exception\client
 * @class   froq\http\exception\client\GoneException
 * @author  Kerem Güneş
 * @since   5.0
 */
class GoneException extends ClientException
{
    /** Code as status code. */
    public final const CODE = Status::GONE;
}
