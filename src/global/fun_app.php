<?php
/********************************
 * Global app functions module. *
 ********************************/

/**
 * Shortcut for app address.
 * @param  string $prop
 * @return Froq\App|Froq\App\?|null
 * @throws \Throwable
 */
function app(string $prop = '') {
    $app = get_global('app');
    if (!strpbrk($prop, '.->')) {
        return (!$prop) ? $app : $app->{$prop};
    }

    // evil or tricky?
    eval('$return = $app->'. str_replace('.', '->', $prop) .';');

    return $return;
}

/**
 * Get app dir.
 * @return string|null
 */
function app_dir()
{
    return defined('APP_DIR') ? APP_DIR : null;
}

/**
 * Get app load time.
 * @return string|null
 */
function app_load_time()
{
    return defined('APP_START_TIME')
        ? sprintf('%.10f', (microtime(true) - APP_START_TIME)) : null;
}
