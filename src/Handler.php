<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use froq\AppError;
use Throwable;

/**
 * Handler.
 *
 * Represents an handler entity that registers / unregisters `error`, `exception` and `shutdown` handlers.
 * This is an internal class and all handlers are constantified internally.
 *
 * @package froq
 * @object  froq\Handler
 * @author  Kerem Güneş
 * @since   4.0
 * @static
 */
final class Handler
{
    /** @internal */
    private static mixed $displayErrors = null;

    /**
     * Register error handler.
     *
     * @return void
     */
    public static function registerErrorHandler(): void
    {
        set_error_handler(function ($ecode, $emesg, $efile, $eline) {
            // @cancel: Because error_get_last() should always work.
            // Error was suppressed with the @ operator.
            // if (!$ecode || !($ecode & error_reporting())) {
            //     return;
            // }

            switch ($ecode) {
                case E_ERROR:
                case E_PARSE:
                case E_STRICT:
                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:
                case E_COMPILE_WARNING:
                    $error = sprintf('Fatal error at %s:%s [code: %s, message: %s]',
                        $efile, $eline,  $ecode, $emesg);
                    break;
                case E_RECOVERABLE_ERROR:
                    $error = sprintf('Recoverable error at %s:%s [code: %s, message: %s]',
                        $efile, $eline, $ecode, $emesg);
                    break;
                case E_NOTICE:
                case E_WARNING:
                case E_DEPRECATED:
                case E_USER_ERROR:
                case E_USER_NOTICE:
                case E_USER_WARNING:
                case E_USER_DEPRECATED:
                    // Get error title.
                    $title = xstring(get_constant_name($ecode, 'E_'))
                        ->sub(2)->lower()->upper(0)->replace('_', ' ');

                    $error = sprintf('%s at %s:%s [code: %s, message: %s]',
                        $title, $efile, $eline, $ecode, $emesg);
                    break;
                default:
                    $error = sprintf('Unknown error at %s:%s [code: %s, message: %s]',
                        $efile, $eline, $ecode, $emesg);
            }

            // Store, this can be used later to check error stuff.
            app_fail('error', new AppError($error, code: $ecode));

            // @cancel: Because error_get_last() should always work.
            // Dont not execute php internal error handler.
            // return true;
            return false;
        });
    }

    /**
     * Register exception handler.
     *
     * @return void
     */
    public static function registerExceptionHandler(): void
    {
        set_exception_handler(function (Throwable $e) {
            // Store error display option (setting temporarily as no local = no display)
            self::$displayErrors = ini_set('display_errors', __local__);

            // Store, this may be used later to check error stuff.
            app_fail('exception', $e);

            // This will be caught in shutdown handler.
            throw $e;
        });
    }

    /**
     * Register shutdown handler.
     *
     * @return void
     */
    public static function registerShutdownHandler(): void
    {
        register_shutdown_function(function () {
            $error = $errorCode = null;

            // This will keep app running, even if a ParseError occurs.
            if ($fail = app_fail('exception')) {
                $error = [
                    'type' => $fail->getCode(), 'message' => $fail->__toString(),
                    'file' => $fail->getFile(), 'line'    => $fail->getLine()
                ];
                $errorCode = $error['type'];
            } elseif (($fail = app_fail('error'))
                && in_array($fail->getCode(), [E_ERROR, E_USER_ERROR])) {
                $error = [
                    'type' => $fail->getCode(), 'message' => $fail->__toString(),
                    'file' => $fail->getFile(), 'line'    => $fail->getLine()
                ];
                $errorCode = $error['type'];
            } elseif (($fail = error_get_last())
                && in_array($fail['type'] ?? null, [E_ERROR, E_USER_ERROR])) {
                $error     = $fail;
                $errorCode = $fail['type'] ?? -1;
            }

            if ($error) {
                $error = sprintf("Shutdown at %s:%s\nError:\n%s",
                    $error['file'], $error['line'], $error['message']);

                // Call app error process (log etc.).
                app()->error($e = new AppError($error, code: $errorCode));

                // Store, this may be used later to check error stuff.
                app_fail('shutdown', $e);

                // Restore error display option.
                if (self::$displayErrors !== null) {
                    ini_set('display_errors', self::$displayErrors);
                }
            }
        });
    }

    /**
     * Unregister error handler.
     *
     * @return void
     */
    public static function unregisterErrorHandler(): void
    {
        self::$displayErrors = null;

        restore_error_handler();
    }

    /**
     * Unregister exception handler.
     *
     * @return void
     */
    public static function unregisterExceptionHandler(): void
    {
        self::$displayErrors = null;

        restore_exception_handler();
    }
}
