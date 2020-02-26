<?php
/*********************
 * Global functions. *
 *********************/

/**
 * No.
 * @param  ... $ins
 * @return bool
 */
function no(...$ins)
{
    foreach ($ins as $in) {
        if (!$in || ($in instanceof stdClass && !((array) $in))) {
            return true;
        }
    }
    return false;
}

/**
 * Not.
 * @param  ... $ins
 * @return bool
 */
function not(...$ins)
{
    foreach ($ins as $in) {
        if ($in === false) {
            return true;
        }
    }
    return false;
}

/**
 * Upper.
 * @param  any $in
 * @return string|null
 * @since  3.0
 */
function upper($in)
{
    return is_string($in) ? mb_convert_case($in, MB_CASE_UPPER_SIMPLE) : null;
}

/**
 * Lower.
 * @param  any $in
 * @return string|null
 * @since  3.0
 */
function lower($in)
{
    return is_string($in) ? mb_convert_case($in, MB_CASE_LOWER_SIMPLE) : null;
}

/**
 * Len
 * @aliasOf size()
 * @since   3.0
 */
function len($in)
{
    return size($in);
}

/**
 * Size.
 * @param  any $in
 * @return int|null
 * @since  3.0
 */
function size($in)
{
    if (is_string($in))    return strlen($in);
    if (is_countable($in)) return count($in);

    if ($in && is_object($in)) {
        if ($in instanceof stdClass)       return count((array) $in);
        if (method_exists($in, 'count'))   return $in->count();
        if (method_exists($in, 'toArray')) return count($in->toArray());
    }

    return null; // No valid input.
}

/**
 * Slice.
 * @param  array|string $in
 * @param  int          $start
 * @param  int|null     $end
 * @return array|string|null
 * @since  3.0, 4.0 Added back.
 */
function slice($in, $start, $end = null)
{
    if (is_array($in)) {
        return array_slice($in, $start, $end);
    }
    if (is_string($in)) {
        return mb_substr($in, $start, $end ?? mb_strlen($in));
    }

    return null; // No valid input.
}

/**
 * Strip.
 * @param  string      $in
 * @param  string|null $chars
 * @return string
 * @since  3.0
 */
function strip($in, $chars = null)
{
    if ($chars) {
        // RegExp: only ~...~ patterns accepted.
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
 * Split (we missed you so much baby..).
 * @param  string   $sep
 * @param  string   $in
 * @param  int|null $limit
 * @param  int|null $flags
 * @return array
 */
function split($sep, $in, $limit = null, $flags = null)
{
    // RegExp: only "~...~" patterns accepted.
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
 * Unsplit (fun function).
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
 * Remove.
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
 * Replace.
 * @param  string|array $in
 * @param  any          $search
 * @param  any          $replacement
 * @param  bool         $remove
 * @return string|array|null
 * @since  3.0
 */
function replace($in, $search, $replacement, $remove = false)
{
    if (is_string($in)) {
        // RegExp: only ~...~ patterns accepted.
        if (is_string($search) && strlen($search) >= 3 && $search[0] == '~') {
            return !is_callable($replacement)
                ? preg_replace($search, $replacement, $in)
                : preg_replace_callback($search, $replacement, $in);
        }

        return str_replace($search, $replacement, $in);
    }

    if (is_array($in)) {
        $key = array_search($search, $in, true);
        if ($key !== false) {
            $in[$key] = $replacement;
            if ($remove) {
                unset($in[$key]);
            }
        }
        return $in;
    }

    return null; // No valid input.
}

/**
 * Grep.
 * @param  string $in
 * @param  string $pattern
 * @return string|null
 * @since  3.0
 */
function grep($in, $pattern)
{
    preg_match($pattern, $in, $match);
    if (isset($match[1])) {
        return $match[1];
    }
    return null;
}

/**
 * Grep all.
 * @param  string $in
 * @param  string $pattern
 * @return array<string>|null
 * @since  3.15
 */
function grep_all($in, $pattern)
{
    preg_match_all($pattern, $in, $matches);
    if (isset($matches[1])) {
        foreach (array_slice($matches, 1) as $match) {
            $ret[] = $match[0] ?? null;
        }
        return $ret;
    }
    return null;
}
