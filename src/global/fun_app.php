<?php
/*************************
 * Global app functions. *
 *************************/

/**
 * Shortcut for global app address.
 * @return froq\App
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
    return app()->dir();
}

/**
 * App load time.
 * @return string
 */
function app_load_time()
{
    return app()->loadTime();
}
