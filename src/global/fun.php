<?php
/*********************
 * Global functions. *
 *********************/

use froq\util\Util;

/**
 * Set Froq! global key, and init sub-array.
 */
define('__froq', '__froq', true);

if (!isset($GLOBALS[__froq])) {
    $GLOBALS[__froq] = [];
}

/**
 * Set global.
 * @param  string $key
 * @param  any    $value
 * @return void
 */
function set_global($key, $value)
{
    $GLOBALS[__froq][$key] = $value;
}

/**
 * Get global.
 * @param  string $key
 * @param  any    $valueDefault
 * @return any
 */
function get_global($key, $valueDefault = null)
{
    return $GLOBALS[__froq][$key] ?? $valueDefault;
}

/**
 * Delete global.
 * @param  string $key
 * @return void
 * @since  3.0
 */
function delete_global($key)
{
    unset($GLOBALS[__froq][$key]);
}

/**
 * No.
 * @param  ... $vars
 * @return bool
 */
function no(...$vars)
{
    foreach ($vars as $var) {
        if (!$var || ($var instanceof \stdClass && !((array) $var))) {
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
 * Env.
 * @param  string|array $key
 * @param  any|null     $value
 * @return any|void
 */
function env($key, $value = null)
{
    // get
    if ($value === null) {
        if (is_array($key)) {
            $value = [];
            foreach ($key as $ke) {
                $value[] = Util::getEnv($ke);
            }
        } else {
            $value = Util::getEnv($key);
        }
        return $value;
    }

    // set
    Util::setEnv($key, $value);
}

/**
 * Default value getter for null variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_nil($a, $b)
{
    return (nil !== $a) ? $a : $b;
}

/**
 * Default value getter for nil string variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_nils($a, $b)
{
    return (nils !== strval($a)) ? $a : $b;
}

/**
 * Default value getter for empty variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_empty($a, $b)
{
    return $a ? $a : $b;
}

// nö!
function _isset($var) { return isset($var); }
function _empty($var) { return empty($var); }

// safe trim for strict mode
function _trim($var, $chars = null)
{
    return (string) trim((string) $var, (string) ($chars ?? " \t\n\r\0\x0b"));
}

/**
 * E (set/get last error exception, @see error handler).
 * @param  \Throwable|null $e
 * @param             bool $deleteAfterGet
 * @return \Throwable|null
 * @since  3.0
 */
function e($e = null, $deleteAfterGet = true)
{
    $eType = gettype($e);
    static $eKey = '@e';

    // set
    if ($eType == 'object' && $e instanceof \Throwable) {
        set_global($eKey, $e);
    } elseif ($eType == 'array') {
        @[$message, $code, $previous] = $e;
        set_global($eKey, new \Exception($message, $code, $previous));
    }

    // get
    elseif ($e === null) {
        $e = get_global($eKey);
        if ($deleteAfterGet) {
            delete_global($eKey);
        }
        return $e;
    }
}

/**
 * Error function (normally comes from froq/froq).
 */
if (!function_exists('error')) {
    /**
     * Error.
     * @param  bool $clear
     * @return string|null
     * @since  3.0
     */
    function error($clear = false)
    {
        $error = error_get_last();

        if ($error !== null) {
            $error = strtolower($error['message']);
            if (strpos($error, '(')) {
                $error = preg_replace('~(?:.*?:)?.*?:\s*(.+)~', '\1', $error);
            }
            $error = $error ?: 'unknown error';
            $clear && error_clear_last();
        }

        return $error;
    }
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
 * Len (alias of size()).
 * @param  any $input
 * @return int|null
 * @since  3.0
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
    if (is_array($input))   return count($input);
    if (is_string($input))  return mb_strlen($input);
    if (is_numeric($input)) return strlen((string) $input);

    if ($input && is_object($input)) {
        if ($input instanceof \stdClass)      return count((array) $input);
        if (method_exists($input, 'count'))   return $input->count();
        if (method_exists($input, 'toArray')) return count($input->toArray());
        if ($input instanceof \Traversable)   return count((array) iterator_to_array($input));
    }

    return null; // no valid input
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
        // regexp: only ~...~ patterns accepted
        $charsLen = strlen($chars);
        if ($charsLen > 2 && $chars[0] == '~') {
            $rules = substr($chars, 1, ($pos = strrpos($chars, '~')) - 1);
            $modifiers = substr($chars, $pos + 1);
            $pattern = sprintf('~^%s|%s$~%s', $rules, $rules, $modifiers);
            return preg_replace($pattern, '', $input);
        }
    }

    return _trim($input, $chars); // save trim
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
 * @return array|null
 * @since  3.15
 */
function grep_all($input, $pattern) {
    preg_match_all($pattern, $input, $matches);
    if (isset($matches[1])) {
        foreach (array_slice($matches, 1) as $match) {
            $ret[] = $match[0] ?? null;
        }
        return $ret;
    }
    return null;
}

/**
 * Map.
 * @param  array|object $input
 * @param  callable     $func
 * @param  array|string $keys
 * @return array|object
 * @since  3.0
 */
function map($input, $func, $keys = null)
{
    // object check
    $check = is_object($input);
    if ($check) {
        $input = (array) $input;
    }

    if ($keys === null) {
        $input = array_map($func, $input);
    } else { // use key,value
        $keys = ($keys == '*') ? array_keys($input) : $keys;
        foreach ($input as $key => $value) {
            if (in_array($key, $keys)) {
                $input[$key] = $func($key, $value);
            }
        }
    }

    return $check ? (object) $input : $input;
}

/**
 * Filter.
 * @param  array|object $input
 * @param  callable     $func
 * @param  array|string $keys
 * @return array|object
 * @since  3.0
 */
function filter($input, $func = null, $keys = null)
{
    $func = $func ?? function ($value) {
        return strlen((string) $value);
    };

    // object check
    $check = is_object($input);
    if ($check) {
        $input = (array) $input;
    }

    if ($keys === null) {
        $input = array_filter($input, $func);
    } else { // use key,value
        $keys = ($keys == '*') ? array_keys($input) : $keys;
        foreach ($input as $key => $value) {
            if ($func($key, $value)) {
                $input[$key] = $value;
            }
        }
    }

    return $check ? (object) $input : $input;
}

/**
 * We missed you so much baby..
 * @param  string   $delim
 * @param  string   $input
 * @param  int|null $limit
 * @param  int|null $flags
 * @return array
 */
function split($delim, $input, $limit = null, $flags = null)
{
    // regexp: only ~...~ patterns accepted
    $delim_len = strlen($delim);
    if ($delim_len == 0) { // split all
        $delim = '~~u';
        $delim_len = 3;
    }

    if ($delim_len > 2 && $delim[0] == '~') { // regexp
        $ret = (array) preg_split($delim, $input, $limit ?? -1, $flags ?? 1); // no empty=1
    } else {
        $ret = (array) explode($delim, $input, $limit ?? PHP_INT_MAX);
        if ($flags === null) { // no empty=null
            $ret = array_filter($ret, 'strlen');
        }
    }

    // plus: prevent 'undefined index..' error
    if ($limit && $limit > 0 && $limit != count($ret)) {
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
        $search = (string) $search;
        if (strlen($search) > 2 && $search[0] == '~') { // regexp
            $input = !is_callable($replacement)
                ? preg_replace($search, $replacement, $input)
                : preg_replace_callback($search, $replacement, $input);
        } else {
            $input = str_replace($search, $replacement, $input);
        }
    } else {
        $input = null; // no valid input
    }

    return $input;
}

/**
 * Dirty debug (dump) tools.. :(
 */
function _ps($s) {
    if (is_null($s)) return 'NULL';
    if (is_bool($s)) return $s ? 'TRUE' : 'FALSE';
    return preg_replace('~\[(.+?):.+?:(private|protected)\]~', '[\1:\2]', print_r($s, true));
}
function _pd($s) {
    ob_start();
    var_dump($s);
    return preg_replace('~\["?(.+?)"?(:(private|protected))?\]=>\s+~', '[\1\2] => ', _ps(trim(ob_get_clean())));
}
function pre($s, $e=false) {
    echo "<pre>", _ps($s), "</pre>", "\n";
    $e && exit;
}
function prs($s, $e=false) {
    echo _ps($s), "\n";
    $e && exit;
}
function prss(...$ss) {
    foreach ($ss as $s) {
        echo _ps($s), "\n";
    }
}
function prd($s, $e=false) {
    echo _pd($s), "\n";
    $e && exit;
}
function prdd(...$dd) {
    foreach ($dd as $s) {
        echo _pd($s), "\n";
    }
}

/**
 * Load app functions.
 */
require_once __dir__ .'/fun_app.php';
