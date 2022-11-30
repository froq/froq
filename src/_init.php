<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */

// Used to detect local environment.
defined('__local__') || define('__local__',
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) ||
    in_array(strrchr($_SERVER['SERVER_NAME'] ?? '', '.'), ['.local', '.localhost'], true)
);
defined('__LOCAL__') || define('__LOCAL__', __local__);

// Show all errors if local.
if (__local__) {
    ini_set('display_errors', true);
    ini_set('error_reporting', E_ALL);
} else {
    ini_set('display_errors', false);
}

// Load global function files.
require 'fun/fun.php';
require 'fun/fun_app.php';

// Init app and return it.
return froq\App::init();
