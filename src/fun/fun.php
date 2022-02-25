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

/**
 * Empty var checker.
 *
 * @param  any    $in
 * @param  any ...$ins
 * @return bool
 */
function no($in, ...$ins)
{
    foreach ([$in, ...$ins] as $in) {
        if (is_empty($in)) {
            return true;
        }
    }
    return false;
}

/**
 * False var checker.
 *
 * @param  any    $in
 * @param  any ...$ins
 * @return bool
 */
function not($in, ...$ins)
{
    foreach ([$in, ...$ins] as $in) {
        if (is_false($in)) {
            return true;
        }
    }
    return false;
}

/**
 * Length getter.
 *
 * @alias of size()
 * @since 3.0
 */
function len(...$args)
{
    return size(...$args);
}

/**
 * Remove something(s) from an array or string.
 *
 * @param  array|string $in
 * @param  any          $search
 * @return array|string
 * @since  3.0
 */
function remove(array|string $in, $search): array|string
{
    return replace($in, $search, '', true);
}

/**
 * Replace something(s) on an array or string.
 *
 * @param  string|array               $in
 * @param  string|array               $search
 * @param  string|array|callable|null $replacement
 * @param  bool                       $remove @internal
 * @return string|array
 * @since  3.0
 */
function replace(array|string $in, array|string $search, array|string|callable $replacement = null,
    bool $remove = false): array|string
{
    if (is_string($in)) {
        // RegExp: only ~..~ patterns accepted.
        if (is_string($search) && strlen($search) >= 3 && $search[0] == '~') {
            return !is_callable($replacement)
                 ? preg_replace($search, $replacement, $in)
                 : preg_replace_callback($search, $replacement, $in);
        }

        return str_replace($search, $replacement, $in);
    }

    if (is_array($in)) {
        if (is_string($search)) {
            $key = array_search($search, $in, true);
            if ($key !== false) {
                $in[$key] = $replacement;
                if ($remove) unset($in[$key]);
            }
            return $in;
        }

        if (is_array($search)) {
            if ($replacement && is_array($replacement)) {
                return str_replace($search, $replacement, $in);
            }
            return array_replace($in, $search);
        }
    }

    return null; // No valid input.
}
