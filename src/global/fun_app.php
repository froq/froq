<?php
/********************************
 * Global app functions module. *
 ********************************/

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
function app_dir(): string
{
    return APP_DIR;
}

/**
 * App load time.
 * @return string
 */
function app_load_time(): string
{
    return sprintf('%.3f', (microtime(true) - APP_START_TIME));
}
