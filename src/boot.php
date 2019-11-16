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
ob_start();

/**
 * Nil (aka null).
 * @const null
 */
defined('nil') or define('nil', null);

/**
 * Nils (aka null string).
 * @const string
 */
defined('nils') or define('nils', '');

/**
 * None (aka null string but not == '', === '', == null, === null).
 * @const string
 * @since 4.0
 * @internal Used by some function as param default that differs from '' or null etc.
 */
defined('none') or define('none', chr(0));

/**
 * Used to detect local env.
 * @const bool
 */
defined('local') or define('local', in_array(
    strrchr($_SERVER['SERVER_NAME'] ?? '', '.'), ['.local', '.localhost']
));

/**
 * Show all errors if local.
 */
if (local) {
    ini_set('display_errors', 'on');
    ini_set('error_reporting', E_ALL);
}

/**
 * Register autoload (if not skipped in pub/index.php for local dev purporses).
 */
if (!defined('__SKIP_AUTOLOAD')) {
    (require __dir__ .'/Autoload.php')->register();
}

/**
 * Load global base files.
 */
require __dir__ .'/global/def.php';
require __dir__ .'/global/fun.php';

/**
 * Init app with default configs (comes from cfg.php) and return it.
 */
return froq\app\App::init(
    require __dir__ .'/global/cfg.php'
);
