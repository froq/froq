<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
if (!defined('NONE')) {
    define('NONE', '', true);
}

/**
 * Used to detect local env.
 * @const bool
 */
if (!defined('LOCAL')) {
    define('LOCAL', (isset($_SERVER['SERVER_NAME'])
        && !!preg_match('~\.local$~i', $_SERVER['SERVER_NAME'])), true);
}

/**
 * Show all errors if local.
 */
if (local) {
    ini_set('display_errors', 'On');
    ini_set('error_reporting', E_ALL);
}

/**
 * Load global base files.
 */
require(__dir__ .'/global/def.php');
require(__dir__ .'/global/cfg.php');
require(__dir__ .'/global/fun.php');

$autoload = require(__dir__ .'/Autoload.php');
$autoload->register();

/**
 * Init app with default configs (comes from cfg.php).
 * @return Froq\App
 */
return Froq\App::init($cfg);
