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
ob_start();

/**
 * Just for fun.
 * @const null
 */
if (!defined('NIL')) {
    define('NIL', null, true);
}

/**
 * More readable empty strings.
 * @const string
 */
if (!defined('NILS')) {
    define('NILS', '', true);
}

/**
 * Used to detect local env.
 * @const bool
 */
if (!defined('LOCAL')) {
    define('LOCAL', (substr($_SERVER['SERVER_NAME'] ?? '', -6) === '.local'), true);
}

/**
 * Show all errors if local.
 */
if (local) {
    ini_set('display_errors', 'On');
    ini_set('error_reporting', E_ALL);
}

/**
 * Load autoload.
 * @var Froq\Autoload
 */
$autoload = require(__dir__ .'/Autoload.php');
$autoload->register();
unset($autoload);

/**
 * Load global base files.
 */
require(__dir__ .'/global/def.php');
require(__dir__ .'/global/fun.php');

/**
 * Init app with default configs (comes from cfg.php).
 * @return Froq\App
 */
return Froq\App::init(require(__dir__ .'/global/cfg.php'));
