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
 * Global setter.
 * @param  string $key
 * @param  any    $value
 * @return void
 */
function set_global(string $key, $value)
{
    $GLOBALS[__FROQ][$key] = $value;
}

/**
 * Global getter.
 * @param  string $key
 * @param  any    $valueDefault
 * @return any
 */
function get_global(string $key, $valueDefault = null)
{
    return $GLOBALS[__FROQ][$key] ?? $valueDefault;
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
    if ($delimiter[0] == '~' && strlen($delimiter) > 1) {
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
    $size = sizeof($return);
    if ($limit > $size) {
        $return = array_merge($return, array_fill($size, $limit - $size, null));
    }

    return $return;
}

/**
 * Dirty debug tools..
 */
function _prp($s) {
    $p = '';
    if (is_null($s)) {
        $p = 'NULL';
    } elseif (is_bool($s)) {
        $p = $s ? 'TRUE' : 'FALSE';
    } else {
        $p = preg_replace('~\[(.+):(.+):(private|protected)\]~', '[\1:\3]', print_r($s, true));
    }
    return $p;
}
function prs($s, $e=false) {
    print _prp($s) . PHP_EOL;
    $e && exit;
}
function pre($s, $e=false) {
    print '<pre>'. _prp($s) .'</pre>'. PHP_EOL;
    $e && exit;
}
function prd($s, $e=false) {
    print '<pre>'; var_dump($s); print '</pre>'. PHP_EOL;
    $e && exit;
}

/**
 * Load app functions.
 */
require_once __dir__ .'/fun_app.php';
