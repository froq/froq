<?php
/*******************************
 * Default configuration file. *
 *******************************/

// keep globals clean..
return (function() {
    $cfg = [];

    /**
     * App options.
     */
    // load avg
    $cfg['app.loadAvg'] = 85.00;
    // hosts
    $cfg['app.hosts'] = [];
    $cfg['app.hosts'][] = $_SERVER['SERVER_NAME'];
    // protocols
    $cfg['app.http']  = 'http://'. $_SERVER['SERVER_NAME'];
    $cfg['app.https'] = 'https://'. $_SERVER['SERVER_NAME'];
    // defaults
    $cfg['app.language']  = 'en';
    $cfg['app.languages'] = ['en'];
    $cfg['app.timezone']  = 'UTC';
    $cfg['app.encoding']  = 'UTF-8';
    $cfg['app.locale']    = 'en_US';
    $cfg['app.locales']   = ['en_US' => 'English'];
    // app load time (true = all, 1 = dev, 2 = stage, 3 = production, false = none)
    $cfg['app.exposeAppLoadTime'] = true;
    // initial headers
    $cfg['app.headers'] = [];
    $cfg['app.headers']['Expires'] = 'Thu, 19 Nov 1981 08:10:00 GMT';
    $cfg['app.headers']['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0, pre-check=0, post-check=0';
    $cfg['app.headers']['Pragma'] = 'no-cache';
    $cfg['app.headers']['Connection'] = 'close';
    $cfg['app.headers']['X-Powered-By'] = null; // remove
    // security (https://www.owasp.org/index.php/List_of_useful_HTTP_headers)
    $cfg['app.headers']['X-Frame-Options'] = 'SAMEORIGIN';
    $cfg['app.headers']['X-XSS-Protection'] = '1; mode=block';
    $cfg['app.headers']['X-Content-Type-Options'] = 'nosniff';
    // initial cookies
    $cfg['app.cookies'] = [];
    // session
    $cfg['app.session.cookie'] = [];
    $cfg['app.session.cookie']['name'] = 'SID';
    $cfg['app.session.cookie']['domain'] = '';
    $cfg['app.session.cookie']['path'] = '/';
    $cfg['app.session.cookie']['secure'] = false;
    $cfg['app.session.cookie']['httponly'] = false;
    $cfg['app.session.cookie']['lifetime'] = 0;
    $cfg['app.session.cookie']['length'] = 32;
    $cfg['app.session.cookie']['handler'] = null;
    // gzip
    $cfg['app.gzip'] = [];
    $cfg['app.gzip']['use'] = true;
    $cfg['app.gzip']['mode'] = FORCE_GZIP;
    $cfg['app.gzip']['level'] = -1;
    $cfg['app.gzip']['minlen'] = 64;
    // logger
    $cfg['app.logger'] = [];
    $cfg['app.logger']['level'] = 0; // none
    $cfg['app.logger']['directory'] = APP_DIR .'/tmp/log/app/';
    $cfg['app.logger']['filenameFormat'] = 'Y-m-d';

    /**
     * Security options.
     */
    $cfg['app.security'] = [];
    $cfg['app.security']['maxRequest'] = 100;
    $cfg['app.security']['allowEmptyUserAgent'] = false;
    $cfg['app.security']['allowFileExtensionSniff'] = false;

    /**
     * Service options.
     */
    // aliases
    $cfg['app.service.aliases'] = [];
    $cfg['app.service.aliases']['home'] = ['main', /* 'methods' => [] */];
    $cfg['app.service.aliases']['error'] = ['fail', /* 'methods' => [] */];

    /**
     * Misc. options.
     */
    // pager
    $cfg['misc.pager'] = [];
    $cfg['misc.pager']['s'] = 's';    // start
    $cfg['misc.pager']['ss'] = 'ss';  // stop
    $cfg['misc.pager']['limit'] = 10; // limit

    return $cfg;
})();
