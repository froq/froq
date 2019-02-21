<?php
/***************************
 * Default configurations. *
 ***************************/

// keep globals clean..
return (function() {
    $cfg = [];

    /**
     * App options.
     */

    // protocols
    $cfg['http'] = 'http://'. $_SERVER['SERVER_NAME'];
    $cfg['https'] = 'https://'. $_SERVER['SERVER_NAME'];

    // localization
    $cfg['timezone'] = 'UTC';
    $cfg['language'] = 'en';
    $cfg['encoding'] = 'UTF-8';
    $cfg['locales'] = [LC_TIME => 'en_US.UTF-8', /* ... */];

    // initial response headers
    $cfg['headers'] = [];
    $cfg['headers']['Expires'] = 'Thu, 19 Nov 1981 08:10:00 GMT';
    $cfg['headers']['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0, pre-check=0, post-check=0';
    $cfg['headers']['Pragma'] = 'no-cache';
    $cfg['headers']['Connection'] = 'close';
    $cfg['headers']['X-Powered-By'] = null; // null = remove
    // security (https://www.owasp.org/index.php/List_of_useful_HTTP_headers)
    $cfg['headers']['X-Frame-Options'] = 'SAMEORIGIN';
    $cfg['headers']['X-XSS-Protection'] = '1; mode=block';
    $cfg['headers']['X-Content-Type-Options'] = 'nosniff';

    // initial response cookies
    $cfg['cookies'] = [];

    // session
    $cfg['session'] = [];
    $cfg['session']['name'] = 'SID';
    $cfg['session']['hash'] = true;
    $cfg['session']['hashLength'] = 40; // ID length (32, 40, 64, 128)
    $cfg['session']['savePath'] = null;
    $cfg['session']['saveHandler'] = null;
    // session cookie
    $cfg['session']['cookie'] = [];
    $cfg['session']['cookie']['lifetime'] = 0;
    $cfg['session']['cookie']['path'] = '/';
    $cfg['session']['cookie']['domain'] = '';
    $cfg['session']['cookie']['secure'] = false;
    $cfg['session']['cookie']['httponly'] = false;
    $cfg['session']['cookie']['samesite'] = ''; // PHP/7.3

    // request
    $cfg['request'] = [];
    // request json (for decoding request data @see http://php.net/json_decode)
    $cfg['request']['json']['flags'] = 0;
    $cfg['request']['json']['depth'] = 512;
    $cfg['request']['json']['assoc'] = false;

    // response
    $cfg['response'] = [];
    // response json (for encoding response data @see http://php.net/json_encode)
    $cfg['response']['json']['flags'] = 0;
    $cfg['response']['json']['depth'] = 512;
    // response gzip
    $cfg['response']['gzip']['minimumLength'] = 64;
    $cfg['response']['gzip']['mode'] = FORCE_GZIP;
    $cfg['response']['gzip']['level'] = -1;
    $cfg['response']['gzip']['length'] = PHP_INT_MAX;

    // logger
    $cfg['logger'] = [];
    $cfg['logger']['level'] = 0; // none
    $cfg['logger']['directory'] = APP_DIR .'/tmp/log/app/';
    $cfg['logger']['filenameFormat'] = 'Y-m-d';

    /**
     * Security & safety options.
     */

    // hosts (allowed hosts)
    $cfg['hosts'] = [];
    $cfg['hosts'][] = $_SERVER['SERVER_NAME'];

    $cfg['loadAvg'] = 85.00;
    $cfg['exposeAppLoadTime'] = true; // true (all), false (none), 'dev', 'stage', 'production'

    $cfg['security'] = [];
    $cfg['security']['maxRequest'] = 100;
    $cfg['security']['allowEmptyUserAgent'] = false;
    $cfg['security']['allowFileExtensionSniff'] = false;

    /**
     * Service options.
     */

    // aliases
    $cfg['service.aliases'] = [];
    $cfg['service.aliases']['home'] = ['main', /* 'methods' => [] */];
    $cfg['service.aliases']['error'] = ['fail', /* 'methods' => [] */];

    /**
     * Misc. options.
     */

    // pager
    $cfg['pager'] = [];
    $cfg['pager']['s'] = 's';    // start
    $cfg['pager']['ss'] = 'ss';  // stop
    $cfg['pager']['limit'] = 10; // limit

    return $cfg;
})();
