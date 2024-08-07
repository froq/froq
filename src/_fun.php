<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
use froq\{App, AppRegistry};

const FROQ = 'FROQ';

/*********************
 * Global functions. *
 *********************/

// Init global field.
$GLOBALS[FROQ] ??= [];

/**
 * Set a global variable.
 *
 * @param  string $key
 * @param  mixed  $value
 * @return void
 */
function set_global(string $key, mixed $value): void
{
    $GLOBALS[FROQ][$key] = $value;
}

/**
 * Get a global variable/variables.
 *
 * @param  string     $key
 * @param  mixed|null $default
 * @return mixed|null
 */
function get_global(string $key, mixed $default = null): mixed
{
    // All.
    if ($key === '*') {
        $value = $GLOBALS[FROQ];
    }
    // All subs (eg: "foo*" or "foo.*").
    elseif ($key && $key[-1] === '*') {
        $values = [];
        $search = substr($key, 0, -1);
        foreach ($GLOBALS[FROQ] as $key => $value) {
            if ($search && str_starts_with($key, $search)) {
                $values[$key] = $value;
            }
        }
        $value = $values;
    }
    // Sub only (eg: "foo" or "foo.bar").
    else {
        $value = $GLOBALS[FROQ][$key] ?? $default;
    }

    return $value;
}

/**
 * Delete a global variable.
 *
 * @param  string $key
 * @return void
 * @since  3.0
 */
function delete_global(string $key): void
{
    unset($GLOBALS[FROQ][$key]);
}

/*************************
 * Global app functions. *
 *************************/

/**
 * Shortcut for global app object.
 *
 * @return froq\App
 */
function app(): App
{
    return AppRegistry::getApp();
}

/**
 * Get/set an app failure.
 *
 * @param  string     $name
 * @param  mixed|null $value
 * @return mixed
 * @since  4.0
 */
function app_fail(string $name, mixed $value = null): mixed
{
    return (
        func_num_args() === 1
            ? get_global('app.fail.' . $name)
            : set_global('app.fail.' . $name, $value)
    );
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
