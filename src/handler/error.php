<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

/**
 * Error handler.
 * @return callable
 */
return function($ecode, $emesg, $efile, $eline) {
    // error was suppressed with the @-operator
    if (!$ecode || !($ecode & error_reporting())) {
        return;
    }

    $error = null;
    // check error type
    switch ($ecode) {
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_CORE_WARNING:
        case E_COMPILE_ERROR:
        case E_COMPILE_WARNING:
        case E_STRICT:
            $error = sprintf('Runtime error in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline,  $ecode, $emesg);
            break;
        case E_RECOVERABLE_ERROR:
            $error = sprintf('E_RECOVERABLE_ERROR in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline, $ecode, $emesg);
            break;
        case E_USER_ERROR:
            $error = sprintf('E_USER_ERROR in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline, $ecode, $emesg);
            break;
        case E_USER_WARNING:
            $error = sprintf('E_USER_WARNING in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline, $ecode, $emesg);
            break;
        case E_USER_NOTICE:
            $error = sprintf('E_USER_NOTICE in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline, $ecode, $emesg);
            break;
        default:
            $error = sprintf('Unknown error in %s:%s ecode[%s] emesg[%s]',
                $efile, $eline, $ecode, $emesg);
    }

    // throw! exception handler will catch it
    if ($error) {
        throw new \ErrorException($error, $ecode);
    }

    // don't execute php internal error handler
    return true;
};
