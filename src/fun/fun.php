<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

/*********************
 * Global functions. *
 *********************/

/**
 * Init Froq! global.
 */
if (!isset($GLOBALS['@froq'])) {
    $GLOBALS['@froq'] = [];
}

/**
 * Set a global variable.
 *
 * @param  string $key
 * @param  any    $value
 * @return void
 */
function set_global(string $key, $value)
{
    $GLOBALS['@froq'][$key] = $value;
}

/**
 * Get a global variable/variables.
 *
 * @param  string   $key
 * @param  any|null $default
 * @return any|null
 */
function get_global(string $key, $default = null)
{
    // All.
    if ($key === '*') {
        $value = $GLOBALS['@froq'];
    }
    // All subs (eg: "foo*" or "foo.*").
    elseif ($key && $key[-1] === '*') {
        $values = [];
        $search = substr($key, 0, -1);
        foreach ($GLOBALS['@froq'] as $key => $value) {
            if ($search && str_starts_with($key, $search)) {
                $values[$key] = $value;
            }
        }
        $value = $values;
    }
    // Sub only (eg: "foo" or "foo.bar").
    else {
        $value = $GLOBALS['@froq'][$key] ?? $default;
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
function delete_global(string $key)
{
    unset($GLOBALS['@froq'][$key]);
}
