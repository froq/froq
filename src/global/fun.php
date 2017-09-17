<?php
/****************************
 * Global functions module. *
 ****************************/

if (!isset($GLOBALS['@'])) {
    $GLOBALS['@'] = [];
}

/**
 * Global setter.
 * @param  string $key
 * @param  any    $value
 * @return void
 */
function set_global(string $key, $value)
{
    $GLOBALS['@'][$key] = $value;
}

/**
 * Global getter.
 * @param  string $key
 * @param  any    $valueDefault
 * @return any
 */
function get_global(string $key, $valueDefault = null)
{
    return isset($GLOBALS['@'][$key])
        ? $GLOBALS['@'][$key] : $valueDefault;
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
function _isset($var): bool { return isset($var); }
function _empty($var): bool { return empty($var); }

// safe trim for strict mode
function _trim($var, $chrs = " \t\n\r\0\x0B"): string
{
    return trim((string) $var, $chrs);
}

/**
 * We missed you so much baby..
 * @param  string   $delim
 * @param  string   $input
 * @param  int      $limit
 * @param  int|bool $flags
 * @return array
 */
function split(string $delim, string $input, int $limit = null, $flags = 0): array
{
    // only ~...~ patterns accepted
    if ($delim[0] == '~' && strlen($delim) > 1) {
        return preg_split($delim, $input, $limit ?? -1,
            // true means no-empty
            ($flags !== true) ? $flags : PREG_SPLIT_NO_EMPTY);
    }

    $return = explode($delim, $input, $limit ?? PHP_INT_MAX);
    if ($flags) { // no empty
        $return = array_filter($return);
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
        $p = preg_replace('~\[(.+):(.+):(private|protected)\]~', '[\\1:\\3]', print_r($s, 1));
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
require_once(__dir__ .'/fun_app.php');
