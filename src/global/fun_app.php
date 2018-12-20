<?php
/*************************
 * Global app functions. *
 *************************/

/**
 * Shortcut for global app address.
 * @return ?Froq\App
 */
function app()
{
    return get_global('app');
}

/**
 * App dir.
 * @return string
 */
function app_dir()
{
    return APP_DIR;
}

/**
 * App load time.
 * @return string
 */
function app_load_time()
{
    return substr(strval(microtime(true) - APP_START_TIME), 0, 5);
}
