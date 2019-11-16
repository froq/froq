<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
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

use froq\app\AppError;

/**
 * Shutdown handler.
 */
return function() {
    $app = app();

    $error = error_get_last();
    $error = isset($error['type']) && ($error['type'] == E_ERROR) ? $error : null;

    // This will keep app running, even if a ParseError occurs.
    if ($error == null) {
        $error = app_fail('exception');
        if ($error != null) {
            $error = [
                'type' => $error->getCode(), 'message' => get_class($error) .': '. $error->getMessage(),
                'file' => $error->getFile(), 'line' => $error->getLine()
            ];
        }
    }

    if ($error != null) {
        $error = sprintf("Shutdown in %s:%s\n%s", $error['file'], $error['line'], $error['message']);

        // Call app error prosess (log etc.).
        $app->error($e = new AppError($error, -1));

        // This could be used later to check error stuff.
        app_fail('shutdown', $e);

        // Reset error display option (@see exception handler).
        $opt = get_global('app.displayErrors');
        if ($opt !== null) {
            ini_set('display_errors', strval($opt));
        }
    }
};
