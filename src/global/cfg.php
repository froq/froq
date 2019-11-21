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
    $cfg['locales'] = [LC_TIME => 'en_US.UTF-8'];

    // initial response headers
    $cfg['headers'] = [];
    $cfg['headers']['Expires'] = 'Thu, 19 Nov 1981 08:10:00 GMT';
    $cfg['headers']['Cache-Control'] = 'no-cache, no-store, must-revalidate, max-age=0, pre-check=0, post-check=0';
    $cfg['headers']['Pragma'] = 'no-cache';
    $cfg['headers']['Connection'] = 'close';
    $cfg['headers']['X-Powered-By'] = null; // null = remove
    // security (https://www.owasp.org/index.php/List_of_useful_HTTP_headers)
    $cfg['headers']['X-Frame-Options'] = 'SAMEORIGIN';
    $cfg['headers']['X-XSS-Protection'] = '1; mode=block';
    $cfg['headers']['X-Content-Type-Options'] = 'nosniff';

    // initial response cookies
    $cfg['cookies'] = [];
    // $cfg['cookies']['name'] = [value, ?options];

    // session
    $cfg['session'] = [];
    // or with custom options below
    // $cfg['session'] = [];
    // $cfg['session']['name'] = 'SID';
    // $cfg['session']['hash'] = true;
    // $cfg['session']['hashLength'] = 40; // ID length (32, 40)
    // $cfg['session']['savePath'] = null;
    // $cfg['session']['saveHandler'] = null;
    // // session cookie
    // $cfg['session']['cookie'] = [];
    // $cfg['session']['cookie']['lifetime'] = 0;
    // $cfg['session']['cookie']['path'] = '/';
    // $cfg['session']['cookie']['domain'] = '';
    // $cfg['session']['cookie']['secure'] = false;
    // $cfg['session']['cookie']['httponly'] = false;
    // $cfg['session']['cookie']['samesite'] = ''; // PHP/7.3

    // json encode (use | operator for flags, eg: ... |= JSON_PRETTY_PRINT)
    $cfg['json']['encode']['flags'] = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
    $cfg['json']['encode']['depth'] = 512;
    // json decode
    $cfg['json']['decode']['flags'] = JSON_BIGINT_AS_STRING;
    $cfg['json']['decode']['depth'] = 512;
    $cfg['json']['decode']['assoc'] = null;

    // request
    $cfg['request'] = [];

    // for decoding request json data (@see froq\encoding\JsonEncoder::decode())
    $cfg['request']['json'] = $cfg['json']['decode'];

    // for decoding request xml data (@see froq\encoding\XmlEncoder::decode())
    // $cfg['request']['xml']['validateOnParse'] = false;
    // $cfg['request']['xml']['preserveWhiteSpace'] = false;
    // $cfg['request']['xml']['strictErrorChecking'] = false;
    // $cfg['request']['xml']['throwErrors'] = true;
    // $cfg['request']['xml']['flags'] = 0;
    // $cfg['request']['xml']['assoc'] = true;

    // response
    $cfg['response'] = [];

    // for encoding response json data (@see froq\encoding\JsonEncoder::encode())
    $cfg['response']['json'] = $cfg['json']['encode'];

    // for encoding response xml data (@see froq\encoding\XmlEncoder::encode())
    // $cfg['response']['xml']['indent'] = true;
    // $cfg['response']['xml']['indentString'] = "\t";

    // for encoding response gzip data (@see froq\encoding\GzipEncoder)
    $cfg['response']['gzip']['minlen'] = 64; // bytes
    // $cfg['response']['gzip']['mode'] = null;
    // $cfg['response']['gzip']['level'] = null;
    // $cfg['response']['gzip']['length'] = null;

    // display & download stuff
    $cfg['response']['file']['jpegQuality'] = -1; // php's default
    $cfg['response']['file']['rateLimit'] = 2097152; // 2MB

    // logger
    $cfg['logger'] = [];
    $cfg['logger']['level'] = froq\logger\Logger::FAIL | froq\logger\Logger::WARN;
    $cfg['logger']['directory'] = APP_DIR .'/tmp/log';

    /**
     * Security & safety options.
     */

    // hosts (allowed hosts)
    $cfg['hosts'] = [];
    $cfg['hosts'][] = $_SERVER['SERVER_NAME'];

    $cfg['loadAvg'] = 85.00;

    $cfg['security'] = [];
    $cfg['security']['maxRequest'] = 100;
    $cfg['security']['allowEmptyUserAgent'] = false;
    $cfg['security']['allowFileExtensionSniff'] = false;

    /**
     * Service options.
     */

    // service
    $cfg['service'] = [];
    $cfg['service']['allowRealName'] = true;
    // service aliases
    $cfg['service']['aliases'] = [];
    $cfg['service']['aliases']['home'] = ['main', /* 'methods' => [] */];
    $cfg['service']['aliases']['error'] = ['fail', /* 'methods' => [] */];

    /**
     * Misc. options.
     */

    $cfg['exposeAppRuntime'] = true; // true (all), false (none), 'dev', 'test', 'stage', 'production'

    // pager
    // $cfg['pager'] = [];
    // $cfg['pager']['startKey'] = 's';
    // $cfg['pager']['stopKey'] = 'ss';
    // $cfg['pager']['stopMax'] = 100;

    return $cfg;
})();
