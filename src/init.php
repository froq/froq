<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */

// Ensure request scheme.
$_SERVER['REQUEST_SCHEME'] ??= 'http'. (
    (($_SERVER['SERVER_PORT'] ?? '') == '443') ? 's' : ''
);

// Used to detect local environment.
defined('__local__') || define('__local__',
       in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || in_array(strrchr($_SERVER['SERVER_NAME'] ?? '', '.'), ['.local', '.localhost'], true)
);

// Show all errors if local.
if (__local__) {
    ini_set('display_errors', 'on');
    ini_set('error_reporting', E_ALL);
}

// Load global function files.
require 'fun/fun.php';
require 'fun/fun_app.php';

// Init app and return it.
return froq\App::init();
