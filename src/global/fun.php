<?php
/****************************
 * Global functions module. *
 ****************************/

if (!isset($GLOBALS['@'])) {
    $GLOBALS['@'] = [];
}

/**
 * Global setter.
 * @param string $key
 * @param any    $value
 */
function set_global(string $key, $value) {
    $GLOBALS['@'][$key] = $value;
}

/**
 * Global getter.
 * @param  string $key
 * @param  any    $valueDefault
 * @return any
 */
function get_global(string $key, $valueDefault = null) {
    return isset($GLOBALS['@'][$key])
        ? $GLOBALS['@'][$key] : $valueDefault;
}

/**
 * Array getter with dot notation support for sub-array paths.
 * @param  array  $array
 * @param  string $key (aka path)
 * @param  any    $valueDefault
 * @return any
 */
function dig(array $array = null, string $key, $valueDefault = null) {
    if (isset($array[$key])) {
        $value =& $array[$key]; // direct access
    } else {
        $value =& $array;       // trace element path
        foreach (explode('.', $key) as $key) {
            $value =& $value[$key];
        }
    }

    return ($value !== null) ? $value : $valueDefault;
}

/**
 * Default value getter for null variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_null($a, $b) {
    return (null !== $a) ? $a : $b;
}

/**
 * Default value getter for none variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_none($a, $b) {
    return (none !== _trim($a)) ? $a : $b;
}

/**
 * Default value getter for empty variables.
 * @param  any $a
 * @param  any $b
 * @return any
 */
function if_empty($a, $b) {
    return !empty($a) ? $a : $b;
}

/**
 * Some tricky functions.
 */
// nö!
function _isset($var): bool { return isset($var); }
function _empty($var): bool { return empty($var); }

// safe trim for strict mode
function _trim($input, $chrs = " \t\n\r\0\x0B"): string {
    return trim((string) $input, $chrs);
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
