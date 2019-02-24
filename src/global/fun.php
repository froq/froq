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
    return (nils !== trim((string) $a)) ? $a : $b;
}

/**
 * Default value getter for empty variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_empty($a, $b)
{
    return !empty($a) ? $a : $b;
}

/**
 * Some tricky functions.
 */
// nö!
function _isset($var) { return isset($var); }
function _empty($var) { return empty($var); }

// safe trim for strict mode
function _trim($var, $chars = " \t\n\r\0\x0b")
{
    return (string) trim((string) $var, (string) $chars);
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
            $error = preg_replace('~(?:.*?:)?.*?:\s*(.+)~', '\1', strtolower($error['message']));
            $error = $error ?: 'unknown error';
            $clear && error_clear_last();
        }

        return $error;
    }
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
        if ($input instanceof \Traversable)   return count((array) iterator_to_array($input));
        if (method_exists($input, 'count'))   return $input->count();
        if (method_exists($input, 'size'))    return $input->size();
        if (method_exists($input, 'toArray')) return count($input->toArray());
    }

    return null; // no valid input
}

/**
 * Strip.
 * @param  string|null $input
 * @param  string|null $chars
 * @param  int         $side
 * @return string
 * @since  3.0
 */
function strip($input, string $chars = null, int $side = 0)
{
    $input = (string) $input;
    $chars = $chars ?? " \t\n\r\0\x0b";
    return $side == 0 ? trim($input, $chars) : ($side == 1 ? ltrim($input, $chars) : rtrim($input, $chars));
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
    if (is_array($input)) return array_slice($input, $offset, $length);
    if (is_string($input)) return substr($input, $offset, $length ?? strlen($input));

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
 * @return ?array
 */
function grep(string $input, string $pattern, int $i = 1): ?array
{
    if (preg_match_all($pattern, $input, $matches) && isset($matches[$i])) {
        return $matches[$i];
    }
    return null;
}

/**
 * Map.
 * @param  array    $input
 * @param  callable $func
 * @param  int      $option
 * @return array
 */
function map(array $input, callable $func, int $option = 0): array
{
    // use value,key
    if ($option == 1) {
        foreach ($input as $key => $value) {
            $input[$key] = $func($value, $key);
        }
        return $input;
    }

    return array_map($func, $input);
}

/**
 * Filter.
 * @param  array    $input
 * @param  callable $func
 * @param  int      $option
 * @return array
 */
function filter(array $input, callable $func = null, int $option = 0): array
{
    $func = $func ?? function ($value) {
        return strlen((string) $value);
    };

    return array_filter($input, $func, $option);
}

/**
 * We missed you so much baby..
 * @param  string        $delimiter
 * @param  string        $input
 * @param  int|null      $limit
 * @param  int|null      $flags
 * @param  callable|null $map
 * @param  callable|null $filter
 * @param  string|null   $filter
 * @return array
 */
function split(string $delimiter, string $input, int $limit = null, int $flags = null,
    callable $map = null, callable $filter = null, string $unsplit = null)
{
    // regexp: only ~...~ patterns accepted
    $delimiterLength = strlen($delimiter);
    if ($delimiterLength == 0 /* split all */ ||
        ($delimiterLength >= 2 && $delimiter[0] == '~') /* regexp */) {
        $return = (array) preg_split($delimiter, $input, $limit ?? -1,
            ($flags === null) ? PREG_SPLIT_NO_EMPTY : $flags);
    } else {
        $return = (array) explode($delimiter, $input, $limit ?? PHP_INT_MAX);
        if ($flags === null) { // no empty
            $return = array_filter($return, 'strlen');
        }
    }

    // plus: prevent 'undefined index..' error
    if ($limit && $limit > ($returnSize = count($return))) {
        $return = array_merge($return, array_fill($returnSize, $limit - $returnSize, null));
    }

    // map,filter if provided
    if ($map) $return = map($return, $map);
    if ($filter) $return = filter($return, $filter);

    // yea, some devs are so lazy.. pofff :)
    if ($unsplit) $return = unsplit($unsplit, $return);

    return $return;
}

/**
 * Unsplit (fun function).
 * @param  string $delimiter
 * @param  array  $input
 * @return string
 * @since  3.0
 */
function unsplit(string $delimiter, array $input)
{
    return join($delimiter, $input);
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
function replace($input, $search, $replacement, bool $remove = false)
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
        if (strlen($search) > 2 && $search[0] == '~' /* regexp */) {
            $input = preg_replace($search, $replacement, $input);
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
