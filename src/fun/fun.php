<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
const _FROQ = '_FROQ';

/*********************
 * Global functions. *
 *********************/

/**
 * Init global field.
 */
$GLOBALS[_FROQ] ??= [];

/**
 * Set a global variable.
 *
 * @param  string $key
 * @param  mixed  $value
 * @return void
 */
function set_global(string $key, mixed $value): void
{
    $GLOBALS[_FROQ][$key] = $value;
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
        $value = $GLOBALS[_FROQ];
    }
    // All subs (eg: "foo*" or "foo.*").
    elseif ($key && $key[-1] === '*') {
        $values = [];
        $search = substr($key, 0, -1);
        foreach ($GLOBALS[_FROQ] as $key => $value) {
            if ($search && str_starts_with($key, $search)) {
                $values[$key] = $value;
            }
        }
        $value = $values;
    }
    // Sub only (eg: "foo" or "foo.bar").
    else {
        $value = $GLOBALS[_FROQ][$key] ?? $default;
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
    unset($GLOBALS[_FROQ][$key]);
}
