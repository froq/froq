<?php
/*************************
 * Global app functions. *
 *************************/

/**
 * Shortcut for global app address.
 * @return froq\app\App
 */
function app()
{
    return froq\common\Registry::get('app');
}

/**
 * App root.
 * @return string
 * @since  4.0
 */
function app_root()
{
    return app()->root();
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
 * App env.
 * @return string
 * @since  4.0
 */
function app_env()
{
    return app()->env()->getName();
}

/**
 * App runtime.
 * @return float
 * @since  4.0 Replaced with app_load_time().
 */
function app_runtime()
{
    return app()->runtime();
}

/**
 * App config.
 * @param  string|array $key
 * @param  any          $value_default
 * @return any|froq\config\Config
 * @since  4.0
 */
function app_config($key, $value_default = null)
{
    return app()->config($key, $value_default);
}

/**
 * App fail.
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
 * App fails.
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
