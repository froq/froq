<?php defined('root') or die('Access denied!');
/**
 * Default configuration file.
 */
$cfg = [];

/**
 * App options.
 */
$cfg['app'] = [];

// load avg
$cfg['app']['loadAvg'] = 85.00;

// protocols
$cfg['app']['http'] = 'http://'. $_SERVER['SERVER_NAME'];
$cfg['app']['https'] = 'https://'. $_SERVER['SERVER_NAME'];

// directories
$cfg['app']['dir'] = [];
$cfg['app']['dir']['tmp'] = root .'/../../../.tmp';
$cfg['app']['dir']['class'] = root .'/src/library/class';
$cfg['app']['dir']['function'] = root .'/src/library/function';

// defaults
$cfg['app']['language']  = 'en';
$cfg['app']['languages'] = ['en'];
$cfg['app']['timezone']  = 'UTC';
$cfg['app']['encoding']  = 'utf-8';
$cfg['app']['locale']    = 'en_US';
$cfg['app']['locales']   = ['en_US' => 'English'];

// initial headers
$cfg['app']['headers'] = [];
$cfg['app']['headers']['Expires'] = 'Thu, 19 Nov 1981 08:10:00 GMT';
$cfg['app']['headers']['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0, pre-check=0, post-check=0';
$cfg['app']['headers']['Pragma'] = 'no-cache';
$cfg['app']['headers']['Connection'] = 'close';
$cfg['app']['headers']['X-Powered-By'] = null; // remove
// security (https://www.owasp.org/index.php/List_of_useful_HTTP_headers)
$cfg['app']['headers']['X-Frame-Options'] = 'SAMEORIGIN';
$cfg['app']['headers']['X-XSS-Protection'] = '1; mode=block';
$cfg['app']['headers']['X-Content-Type-Options'] = 'nosniff';

// initial cookies
$cfg['app']['cookies'] = [];

// session
$cfg['app']['session'] = [];
$cfg['app']['session']['cookie'] = [];
$cfg['app']['session']['cookie']['name'] = 'SID';
$cfg['app']['session']['cookie']['domain'] = '';
$cfg['app']['session']['cookie']['path'] = '/';
$cfg['app']['session']['cookie']['secure'] = false;
$cfg['app']['session']['cookie']['httponly'] = false;
$cfg['app']['session']['cookie']['lifetime'] = 0;
$cfg['app']['session']['cookie']['length'] = 128; // 128-byte

// gzip
$cfg['app']['gzip'] = [];
$cfg['app']['gzip']['use'] = true;
$cfg['app']['gzip']['mode'] = FORCE_GZIP;
$cfg['app']['gzip']['level'] = -1;
$cfg['app']['gzip']['minlen'] = 128;

// logger
$cfg['app']['logger'] = [];
$cfg['app']['logger']['level'] = 0; // none
$cfg['app']['logger']['directory'] = $cfg['app']['dir']['tmp'] .'/log/app/';
$cfg['app']['logger']['filenameFormat'] = 'Y-m-d';

/**
 * Security options.
 */
$cfg['app']['security'] = [];
$cfg['app']['security']['maxRequest'] = 100;
$cfg['app']['security']['allowEmptyUserAgent'] = false;
$cfg['app']['security']['allowFileExtensionSniff'] = false;

/**
 * Etc. options.
 */
$cfg['etc'] = [];

// redirect
$cfg['etc']['redirect'] = [];
$cfg['etc']['redirect']['key'] = '_to';
$cfg['etc']['redirect']['fallbackLocation'] = '/';

// pager
$cfg['etc']['pager'] = [];
$cfg['etc']['pager']['s'] = 's';    // start
$cfg['etc']['pager']['ss'] = 'ss';  // stop
$cfg['etc']['pager']['limit'] = 10; // limit

return $cfg;
