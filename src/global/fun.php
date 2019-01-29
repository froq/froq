<?php
/*********************
 * Global functions. *
 *********************/

/**
 * Set Froq! global key, and init sub-array.
 */
define('__FROQ', '__froq');

if (!isset($GLOBALS[__FROQ])) {
    $GLOBALS[__FROQ] = [];
}

/**
 * Set global.
 * @param  string $key
 * @param  any    $value
 * @return void
 */
function set_global($key, $value)
{
    $GLOBALS[__FROQ][$key] = $value;
}

/**
 * Get global.
 * @param  string $key
 * @param  any    $valueDefault
 * @return any
 */
function get_global($key, $valueDefault = null)
{
    return $GLOBALS[__FROQ][$key] ?? $valueDefault;
}

/**
 * Delete global.
 * @param  string $key
 * @return void
 * @since  3.0
 */
function delete_global($key)
{
    unset($GLOBALS[__FROQ][$key]);
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
function _trim($var, $chars = " \t\n\r\0\x0B")
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
 * We missed you so much baby..
 * @param  string   $delimiter
 * @param  string   $input
 * @param  int|bool $limit
 * @param  int|bool $flags
 * @return array
 */
function split(string $delimiter, string $input, $limit = null, $flags = 0)
{
    // swap args
    if ($limit === true) {
        $flags = true;
        $limit = null;
    }

    // regexp: only ~...~ patterns accepted
    $delimiterLength = strlen($delimiter);
    if ($delimiterLength == 0 /* split all */ || ($delimiterLength >= 2 && $delimiter[0] == '~')) {
        $return = (array) preg_split($delimiter, $input, $limit ?? -1,
            // true=no empty
            ($flags === true) ? PREG_SPLIT_NO_EMPTY : $flags);
    } else {
        $return = (array) explode($delimiter, $input, $limit ?? PHP_INT_MAX);
        if ($flags === true) { // no empty
            $return = array_filter($return, 'strlen');
        }
    }

    // plus: prevent 'undefined index..' error
    $returnSize = sizeof($return);
    if ($limit > $returnSize) {
        $return = array_merge($return, array_fill($returnSize, $limit - $returnSize, null));
    }

    return $return;
}

/**
 * Dirty debug tools..
 */
function _prp($s) {
    if (is_null($s)) return 'NULL';
    if (is_bool($s)) return $s ? 'TRUE' : 'FALSE';
    return preg_replace('~\["(.+?)":(.+?):(private|protected)\]~', '[\1:\3]', print_r($s, true));
}
function _prd($s, $e=false) {
    ob_start();
    var_dump($s);
    return preg_replace('~\["?(.+?)"?(:(private|protected))?\]=>\s+~', '[\1\2] => ', _prp(ob_get_clean()));
}
function prs($s, $e=false) {
    echo _prp($s), "\n";
    $e && exit;
}
function pre($s, $e=false) {
    echo "<pre>", _prp($s), "</pre>", "\n";
    $e && exit;
}
function prr(...$ss) {
    foreach ($ss as $s) {
        echo _prp($s), "\n";
    }
}
function prd($s, $e=false) {
    echo _prd($s);
    $e && exit;
}

/**
 * Load app functions.
 */
require_once __dir__ .'/fun_app.php';
