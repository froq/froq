<?php
/*********************
 * Global functions. *
 *********************/

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
        if (!$var) return true;
        if ($var instanceof \stdClass && !((array) $var)) return true;
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
        if ($var === false) return true;
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
            $ret = [];
            foreach ($key as $ke) {
                $ret[] = \froq\util\Util::getEnv($ke);
            }
        } else {
            $ret = \froq\util\Util::getEnv($key);
        }
        return $ret;
    }

    // set
    \froq\util\Util::setEnv($key, $value);
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
    function error(bool $clear = false)
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
    return is_string($input) ? strtoupper($input) : null;
}

/**
 * Lower.
 * @param  any $input
 * @return string|null
 * @since  3.0
 */
function lower($input)
{
    return is_string($input) ? strtolower($input) : null;
}

/**
 * Has.
 * @param  array|string $input
 * @param  any          $search
 * @param  bool         $strict
 * @return bool|null
 * @since  3.0
 */
function has($input, $search, bool $strict = true)
{
    if (is_string($input)) {
        return false !== ($strict ? strpos($input, (string) $search) : stripos($input, (string) $search));
    }
    if (is_array($input)) {
        return in_array($search, $input, $strict);
    }
    if (is_object($input)) {
        return in_array($search, get_class_vars($input), $strict);
    }

    return null; // no valid input
}

/**
 * Has key.
 * @param  array|object $input
 * @param  int|string   $key
 * @return bool|null
 * @since  3.0
 */
function has_key($input, $key)
{
    if (is_array($input)) {
        return array_key_exists($key, $input);
    }
    if (is_object($input)) {
        return array_key_exists($key, get_class_vars($input));
    }

    return null; // no valid input
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
    if (is_string($input))  return strlen($input);
    if (is_numeric($input)) return strlen((string) $input);

    if ($input && is_object($input)) {
        if ($input instanceof \stdClass)      return count((array) $input);
        if (method_exists($input, 'count'))   return $input->count();
        if (method_exists($input, 'size'))    return $input->size();
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
function strip($input, string $chars = null)
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
 * Slice.
 * @param  array|string $input
 * @param  int          $offset
 * @param  int|null     $length
 * @return array|string|null
 * @since  3.0
 */
function slice($input, int $offset, int $length = null)
{
    if (is_array($input)) {
        return array_slice($input, $offset, $length);
    }
    if (is_string($input)) {
        return substr($input, $offset, $length ?? strlen($input));
    }

    return null; // no valid input
}

/**
 * Unslice (fun function).
 * @param  array|string $input1
 * @param  array|string $input2
 * @return array|string|null
 * @since  3.0
 */
function unslice($input1, $input2)
{
    if (is_array($input1) && is_array($input2)) {
        return array_merge($input1, $input2);
    }
    if (is_string($input1) && is_string($input2)) {
        return $input1 . $input2;
    }

    return null; // no valid input
}

/**
 * Grep.
 * @param  string $input
 * @param  string $pattern
 * @param  int    $i
 * @return array|null
 * @since  3.0
 */
function grep(string $input, string $pattern, int $i = 1)
{
    if (preg_match_all($pattern, $input, $matches) && isset($matches[$i])) {
        return $matches[$i];
    }
    return null;
}

/**
 * Pad.
 * @param  array    $array
 * @param  int      $size
 * @param  any|null $value
 * @return array
 * @since  3.1
 */
function pad(array $array, int $size, $value = null)
{
    return array_pad($array, $size, $value);
}

/**
 * Map.
 * @param  array|object $input
 * @param  callable     $func
 * @param  int          $option
 * @return array|object
 * @since  3.0
 */
function map($input, callable $func, int $option = 0)
{
    // use value,key
    if ($option == 1) {
        foreach ($input as $key => $value) {
            $input[$key] = $func($value, $key);
        }
        return $input;
    }

    $is_object = is_object($input);
    if ($is_object) {
        $input = (array) $input;
    }

    $input = array_map($func, $input);
    if ($is_object) {
        $input = (object) $input;
    }

    return $input;
}

/**
 * Filter.
 * @param  array|object $input
 * @param  callable     $func
 * @param  int          $option
 * @return array|object
 * @since  3.0
 */
function filter($input, callable $func = null, int $option = 0)
{
    $func = $func ?? function ($value) {
        return strlen((string) $value);
    };

    $is_object = is_object($input);
    if ($is_object) {
        $input = (array) $input;
    }

    $input = array_filter($input, $func, $option);
    if ($is_object) {
        $input = (object) $input;
    }

    return $input;
}

/**
 * We missed you so much baby..
 * @param  string        $delim
 * @param  string        $input
 * @param  int|null      $limit
 * @param  int|null      $flags
 * @return array
 */
function split(string $delim, string $input, int $limit = null, int $flags = null)
{
    // regexp: only ~...~ patterns accepted
    $delimLen = strlen($delim);
    if ($delimLen == 0) { // split all
        $delim = '~~u';
        $delimLen = 3;
    }

    if ($delimLen > 2 && $delim[0] == '~') { // regexp
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
function unsplit(string $delim, array $input)
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
        $key = array_search($search, $input);
        if ($key !== false && array_key_exists($key, $input)) {
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
