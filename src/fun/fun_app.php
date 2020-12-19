<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=0);

/*************************
 * Global app functions. *
 *************************/

use froq\common\object\Registry;

/**
 * Shortcut for global app object.
 *
 * @return froq\App
 */
function app()
{
    return Registry::get('@app');
}

/**
 * get app root.
 *
 * @return string
 * @since  4.0
 */
function app_root()
{
    return app()->root();
}

/**
 * Get app dir.
 *
 * @return string
 */
function app_dir()
{
    return app()->dir();
}

/**
 * Get app env.
 *
 * @return string
 * @since  4.0
 */
function app_env()
{
    return app()->env();
}

/**
 * Get app runtime result.
 *
 * @return float
 * @since  4.0 Replaced with app_load_time().
 */
function app_runtime()
{
    return app()->runtime();
}

/**
 * Get app config or default.
 *
 * @param  string|array $key
 * @param  any          $default
 * @return any|froq\config\Config
 * @since  4.0
 */
function app_config($key, $default = null)
{
    return app()->config($key, $default);
}

/**
 * Get/set an app failure.
 *
 * @param  string   $name
 * @param  any|null $value
 * @return any|null
 * @since  4.0
 */
function app_fail($name, $value = null)
{
    return (func_num_args() == 1)
         ? get_global('app.fail.'. $name)
         : set_global('app.fail.'. $name, $value);
}

/**
 * Get app failures.
 *
 * @return array
 * @since  4.0
 */
function app_fails()
{
    $ret = [];

    $fails = get_global('app.fail.*');
    if ($fails) {
        foreach ($fails as $name => $fail) {
            $ret[$name] = $fail;
        }
    }

    return $ret;
}
