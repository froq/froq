<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=0);

/*********************
 * Global functions. *
 *********************/

/**
 * Empty var checker.
 *
 * @param  any $in
 * @param  ... $ins
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
 * @param  any $in
 * @param  ... $ins
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
 * Upper-caser.
 *
 * @param  any $in
 * @return string|null
 * @since  3.0
 */
function upper($in)
{
    return is_string($in) ? strtoupper($in) : null;
}

/**
 * Lower-caser.
 *
 * @param  any $in
 * @return string|null
 * @since  3.0
 */
function lower($in)
{
    return is_string($in) ? strtolower($in) : null;
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
 * Strip a string, with RegExp (~) option.
 *
 * @param  string      $in
 * @param  string|null $chars
 * @return string
 * @since  3.0
 */
function strip($in, $chars = null)
{
    if ($chars) {
        // RegExp: only ~..~ patterns accepted.
        if (strlen($chars) >= 3 && $chars[0] == '~') {
            $rules = substr($chars, 1, ($pos = strrpos($chars, '~')) - 1);
            $modifiers = substr($chars, $pos + 1);

            return preg_replace(sprintf('~^%s|%s$~%s', $rules, $rules, $modifiers), '', $in);
        }

        return trim($in, $chars);
    }

    return trim($in);
}

/**
 * Split, missed you so much baby..
 *
 * @param  string   $sep
 * @param  string   $in
 * @param  int|null $limit
 * @param  int|null $flags
 * @return array
 */
function split($sep, $in, $limit = null, $flags = null)
{
    // RegExp: only "~..~" patterns accepted.
    $seplen = strlen($sep);
    if ($seplen == 0) {
        $sep = '~~u'; // Split all.
        $seplen = 3;
    }

    if ($seplen >= 3 && $sep[0] == '~') { // RegExp.
        $ret = (array) preg_split($sep, $in, $limit ?? -1, $flags ?? 1); // 1=No empty.
    } else {
        $ret = (array) explode($sep, $in, $limit ?? PHP_INT_MAX);
        if ($flags === null) { // Null=no empty.
            $ret = array_filter($ret, 'strlen');
        }
    }

    // Plus: prevent 'undefined index..' error.
    if ($limit && $limit != count($ret)) {
        $ret = array_pad($ret, $limit, null);
    }

    return $ret;
}

/**
 * Unsplit, a fun function.
 *
 * @param  string $sep
 * @param  array  $in
 * @return string
 * @since  3.0
 */
function unsplit($sep, $in)
{
    return join($sep, $in);
}

/**
 * Remove something(s) from an array or string.
 *
 * @param  array|string $in
 * @param  any          $search
 * @return array|string|null
 * @since  3.0
 */
function remove($in, $search)
{
    return replace($in, $search, '', true);
}

/**
 * Replace something(s) on an array or string.
 *
 * @param  string|array               $in
 * @param  string|array               $search
 * @param  string|array|callable|null $replacement
 * @param  bool                       $remove
 * @return string|array|null
 * @since  3.0
 */
function replace($in, $search, $replacement = null, $remove = false)
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
