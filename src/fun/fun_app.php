<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

/*************************
 * Global app functions. *
 *************************/

use froq\App;
use froq\common\object\Registry;

/**
 * Shortcut for global app object.
 *
 * @return froq\App
 */
function app(): App
{
    return Registry::get('@app');
}

/**
 * Get app root.
 *
 * @return string
 * @since  4.0
 */
function app_root(): string
{
    return app()->root();
}

/**
 * Get app dir.
 *
 * @return string
 */
function app_dir(): string
{
    return app()->dir();
}

/**
 * Get app env.
 *
 * @return string
 * @since  4.0
 */
function app_env(): string
{
    return app()->env();
}

/**
 * Get app runtime result.
 *
 * @param  int  $precision
 * @param  bool $format
 * @return float|string
 * @since  4.0
 */
function app_runtime(int $precision = 3, bool $format = false): float|string
{
    return app()->runtime($precision, $format);
}

/**
 * Get app config or default.
 *
 * @param  string|array $key
 * @param  any|null     $default
 * @return any|null|froq\common\object\Config
 * @since  4.0
 */
function app_config(string|array $key, $default = null)
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
function app_fail(string $name, $value = null)
{
    return (func_num_args() == 1)
         ? get_global('app.fail.'. $name)
         : set_global('app.fail.'. $name, $value);
}

/**
 * Get app failures.
 *
 * @return array|null
 * @since  4.0
 */
function app_fails(): array|null
{
    if ($fails = get_global('app.fail.*')) {
        foreach ($fails as $name => $fail) {
            $ret[$name] = $fail;
        }
    }

    return $ret ?? null;
}

/**
 * Get app registry.
 *
 * @return froq\common\object\Registry
 */
function registry(): Registry
{
    return app()::registry();
}
