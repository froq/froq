<?php
/*********************
 * Global functions. *
 *********************/

/**
 * No.
 * @param  ... $vars
 * @return bool
 */
function no(...$vars)
{
    foreach ($vars as $var) {
        if (!$var || ($var instanceof stdClass && !((array) $var))) {
            return true;
        }
    }
    return false;
}

/**
 * Not.
 * @param  ... $vars
 * @return bool
 */
function not(...$vars)
{
    foreach ($vars as $var) {
        if ($var === false) {
            return true;
        }
    }
    return false;
}

/**
 * Upper.
 * @param  any $input
 * @return string|null
 * @since  3.0
 */
function upper($input)
{
    return is_string($input) ? mb_strtoupper($input) : null;
}

/**
 * Lower.
 * @param  any $input
 * @return string|null
 * @since  3.0
 */
function lower($input)
{
    return is_string($input) ? mb_strtolower($input) : null;
}

/**
 * Len
 * @aliasOf size()
 * @since   3.0
 */
function len($input)
{
    return size($input);
}

/**
 * Size.
 * @param  any $input
 * @return int|null
 * @since  3.0
 */
function size($input)
{
    if (is_string($input))    return strlen($input);
    if (is_countable($input)) return count($input);

    if ($input && is_object($input)) {
        if ($input instanceof stdClass)       return count((array) $input);
        if (method_exists($input, 'count'))   return $input->count();
        if (method_exists($input, 'toArray')) return count($input->toArray());
    }

    return null; // No valid input.
}

/**
 * Slice.
 * @param  array|string $input
 * @param  int          $start
 * @param  int|null     $end
 * @return array|string|null
 * @since  3.0, 4.0 Added back.
 */
function slice($input, $start, $end = null)
{
    if (is_array($input)) {
        return array_slice($input, $start, $end);
    }
    if (is_string($input)) {
        return mb_substr($input, $start, $end ?? mb_strlen($input));
    }

    return null; // No valid input.
}

/**
 * Strip.
 * @param  string      $input
 * @param  string|null $chars
 * @return string
 * @since  3.0
 */
function strip($input, $chars = null)
{
    if ($chars != null) {
        // Regexp: only ~...~ patterns accepted.
        if (strlen($chars) >= 3 && $chars[0] == '~') {
            $rules = substr($chars, 1, ($pos = strrpos($chars, '~')) - 1);
            $modifiers = substr($chars, $pos + 1);

            return preg_replace(sprintf('~^%s|%s$~%s', $rules, $rules, $modifiers), '', $input);
        }

        return trim($input, $chars);
    }

    return trim($input);
}

/**
 * Split (we missed you so much baby..).
 * @param  string   $delim
 * @param  string   $input
 * @param  int|null $limit
 * @param  int|null $flags
 * @return array
 */
function split($delim, $input, $limit = null, $flags = null)
{
    // Regexp: only "~...~" patterns accepted.
    $delimlen = strlen($delim);
    if ($delimlen == 0) {
        $delim = '~~u'; // Split all.
        $delimlen = 3;
    }

    if ($delimlen >= 3 && $delim[0] == '~') { // Regexp.
        $ret = (array) preg_split($delim, $input, $limit ?? -1, $flags ?? 1); // 1=No empty.
    } else {
        $ret = (array) explode($delim, $input, $limit ?? PHP_INT_MAX);
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
 * @param  string $delim
 * @param  array  $input
 * @return string
 * @since  3.0
 */
function unsplit($delim, $input)
{
    return join($delim, $input);
}

/**
 * Remove.
 * @param  array|string $input
 * @param  any          $search
 * @return array|string|null
 * @since  3.0
 */
function remove($input, $search)
{
    return replace($input, $search, '', true);
}

/**
 * Replace.
 * @param  array|string $input
 * @param  any          $search
 * @param  any          $replacement
 * @param  bool         $remove
 * @return array|string|null
 * @since  3.0
 */
function replace($input, $search, $replacement, $remove = false)
{
    if (is_array($input)) {
        $key = array_search($search, $input, true);
        if ($key !== false) {
            if ($remove) {
                unset($input[$key]);
            } else { $input[$key] = $replacement; }
        }
    } elseif (is_string($input)) {
        if (is_string($search) && strlen($search) >= 3 && $search[0] == '~') { // Regexp.
            $input = !is_callable($replacement)
                ? preg_replace($search, $replacement, $input)
                : preg_replace_callback($search, $replacement, $input);
        } else {
            $input = str_replace($search, $replacement, $input);
        }
    } else {
        $input = null; // No valid input.
    }

    return $input;
}

/**
 * Grep.
 * @param  string $input
 * @param  string $pattern
 * @return string|null
 * @since  3.0
 */
function grep($input, $pattern)
{
    preg_match($pattern, $input, $match);
    if (isset($match[1])) {
        return $match[1];
    }
    return null;
}

/**
 * Grep all.
 * @param  string $input
 * @param  string $pattern
 * @return array<string>|null
 * @since  3.15
 */
function grep_all($input, $pattern)
{
    preg_match_all($pattern, $input, $matches);
    if (isset($matches[1])) {
        foreach (array_slice($matches, 1) as $match) {
            $ret[] = $match[0] ?? null;
        }
        return $ret;
    }
    return null;
}
