<?php
ob_start();

define('APP_START_TIME', microtime(true));

define('nil', null, true);
define('none', '', true);

define('local', (
    isset($_SERVER['SERVER_NAME']) &&
        (bool) strstr($_SERVER['SERVER_NAME'], '.local')), true);

if (local) {
    ini_set('display_errors', '1');
    ini_set('error_reporting', E_ALL);
}

require(__dir__ .'/global/def.php');
require(__dir__ .'/global/cfg.php');
require(__dir__ .'/global/fun.php');

$autoload = require(__dir__ .'/Autoload.php');
$autoload->register();

return Froq\App::init($cfg);
