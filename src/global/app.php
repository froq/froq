<?php
/**
 * Global app functions.
 */

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
