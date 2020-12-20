<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

/*********************
 * Global functions. *
 *********************/

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
        if (!$in) {
            return true;
        }
        if ($in instanceof stdClass && !((array) $in)) {
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
        if ($in === false) {
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
