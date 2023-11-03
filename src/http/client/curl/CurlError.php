<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client\curl;

/**
 * An error class that contains some methods that can be used to detect most
 * occurring cURL errors. For more error checks can be done with `CURLE_*`
 * constants using `CurlError.getCode()` method.
 *
 * @package froq\http\client\curl
 * @class   froq\http\client\curl\CurlError
 * @author  Kerem Güneş
 * @since   4.0
 */
class CurlError extends \froq\common\Error
{
    /**
     * Check for CURLE_URL_MALFORMAT(3).
     *
     * @return bool
     */
    public function isBadUrl(): bool
    {
        return ($this->code === CURLE_URL_MALFORMAT);
    }

    /**
     * Check for CURLE_COULDNT_RESOLVE_HOST(6).
     *
     * @return bool
     */
    public function isBadHost(): bool
    {
        return ($this->code === CURLE_COULDNT_RESOLVE_HOST);
    }

    /**
     * Check for CURLE_OPERATION_TIMEDOUT(28)/CURLE_OPERATION_TIMEOUTED(28).
     *
     * @return bool
     */
    public function isTimeout(): bool
    {
        return ($this->code === CURLE_OPERATION_TIMEDOUT);
    }
}
