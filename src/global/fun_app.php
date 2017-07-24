<?php
/********************************
 * Global app functions module. *
 ********************************/

/**
 * Shortcut for app address.
 * @return ?Froq\App
 * @throws \Throwable
 */
function app() {
    return get_global('app');
}

/**
 * Get app dir.
 * @return string
 */
function app_dir()
{
    return APP_DIR;
}

/**
 * Get app load time.
 * @return string
 */
function app_load_time()
{
    return sprintf('%.3f', (microtime(true) - APP_START_TIME));
}
