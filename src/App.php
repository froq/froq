<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq;

use froq\util\Util;
use froq\util\traits\{SingletonTrait, OneRunTrait};
use froq\event\Events;
use froq\config\Config;
use froq\logger\Logger;
use froq\session\Session;
use froq\database\Database;
use froq\http\{Http, Request, Response};
use froq\service\{Service, ServiceFactory};

/**
 * App.
 * @package froq
 * @object  froq\App
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class App
{
    /**
     * Singleton trait.
     * @object froq\util\traits\SingletonTrait
     */
    use SingletonTrait;

    /**
     * One run trait.
     * @object froq\util\traits\OneRunTrait
     */
    use OneRunTrait;

    /**
     * App envs.
     * @const string
     */
    public const ENV_DEV        = 'dev',
                 ENV_STAGE      = 'stage',
                 ENV_PRODUCTION = 'production';

    /**
     * App dir.
     * @const string
     */
    private $dir;

    /**
     * App env.
     * @const string
     */
    private $env;

    /**
     * App root (provides options like "app.local/v1/book/1" for versioning etc.).
     * @const string
     */
    private $root = '/';

    /**
     * Config.
     * @var froq\config\Config
     */
    private $config;

    /**
     * Logger.
     * @var froq\logger\Logger
     */
    private $logger;

    /**
     * Events.
     * @var froq\events\Events
     */
    private $events;

    /**
     * Service.
     * @var froq\service\Service
     */
    private $service;

    /**
     * Request.
     * @var froq\http\Request
     */
    private $request;

    /**
     * Response.
     * @var froq\http\Response
     */
    private $response;

    /**
     * Session.
     * @var froq\session\Session
     */
    private $session;

    /**
     * Db.
     * @var froq\database\Database
     */
    private $db;

    /**
     * Constructor.
     * @param  array $config
     * @throws froq\AppException
     */
    private function __construct(array $config)
    {
        // @see skeleton/pub/index.php
        if (!defined('APP_DIR')) {
            throw new AppException('APP_DIR is not defined');
        }
        $this->dir = APP_DIR;

        $this->logger = new Logger();
        $this->events = new Events();

        // set default config first
        $this->applyConfig($config);

        // set app as global (@see app() function)
        set_global('app', $this);

        // load core app globals if exists
        if (file_exists($file = "{$this->dir}/app/global/def.php")) {
            include $file;
        }
        if (file_exists($file = "{$this->dir}/app/global/fun.php")) {
            include $file;
        }

        // set handlers
        set_error_handler(require __dir__ .'/handler/error.php');
        set_exception_handler(require __dir__ .'/handler/exception.php');
        register_shutdown_function(require __dir__ .'/handler/shutdown.php');
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Dir.
     * @return string
     */
    public function dir(): string
    {
        return $this->dir;
    }

    /**
     * Env.
     * @return string
     */
    public function env(): string
    {
        return $this->env;
    }

    /**
     * Root.
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Config.
     * @param  string $key
     * @param  any    $valueDefault
     * @return any|froq\config\Config
     */
    public function config(string $key = null, $valueDefault = null)
    {
        if ($key === null) {
            return $this->config;
        }
        // set is not allowed, so config readonly and set available in cfg.php's only
        return $this->config->get($key, $valueDefault);
    }

    /**
     * Logger.
     * @return froq\logger\Logger
     */
    public function logger(): Logger
    {
        return $this->logger;
    }

    /**
     * Events.
     * @return froq\events\Events
     */
    public function events(): Events
    {
        return $this->events;
    }

    /**
     * Service.
     * @return ?froq\service\Service
     */
    public function service(): ?Service
    {
        return $this->service;
    }

    /**
     * Request.
     * @return ?froq\http\Request
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Response.
     * @return ?froq\http\Response
     */
    public function response(): ?Response
    {
        return $this->response;
    }

    /**
     * Session.
     * @return ?froq\session\Session
     * @since  3.18
     */
    public function session(): ?Session
    {
        return $this->session;
    }

    /**
     * Db.
     * @return ?froq\database\Database
     */
    public function db(): ?Database
    {
        return $this->db;
    }

    /**
     * Run.
     * @param  array options
     * @return void
     * @throws froq\AppException
     */
    public function run(array $options): void
    {
        // run once
        $this->___checkRun(new AppException("You cannot call App::run() anymore, it's already ".
                "called in skeleton/pub/index.php once"));

        // apply user options (@see skeleton/pub/index.php)
        if (isset($options['env'])) $this->env = $options['env'];
        if (isset($options['root'])) $this->root = $options['root'];
        if (isset($options['config'])) $this->applyConfig($options['config']);

        // check env
        if (empty($this->env)) {
            throw new AppException('Application env is not defined');
        }

        // security & performans checks
        $halt = $this->haltCheck();
        if ($halt != null) {
            $this->halt($halt[1], $halt[0]);
        }

        $this->applyDefaults();

        $this->request = new Request($this);
        $this->response = new Response($this);

        // could be emptied by developer to disable session or database (with null)
        if (isset($this->config['session'])) {
            $this->session = new Session($this->config['session']);
        }
        if (isset($this->config['db'])) {
            $this->db = new Database($this);
        }

        // @override
        set_global('app', $this);

        // create service
        $service = ServiceFactory::create($this);
        if ($service == null) {
            throw new AppException('Could not create service');
        }

        $this->startOutputBuffer();

        // here!!
        $this->events->fire('service.beforeRun');
        $output = $service->run();
        $this->events->fire('service.afterRun');

        $this->endOutputBuffer($output);
    }

    /**
     * Is root.
     * @return bool
     */
    public function isRoot(): bool
    {
        return ($this->root === $this->request->uri()->get('path'));
    }

    /**
     * Is dev.
     * @return bool
     */
    public function isDev(): bool
    {
        return ($this->env === self::ENV_DEV);
    }

    /**
     * Is stage.
     * @return bool
     */
    public function isStage(): bool
    {
        return ($this->env === self::ENV_STAGE);
    }

    /**
     * Is production.
     * @return bool
     */
    public function isProduction(): bool
    {
        return ($this->env === self::ENV_PRODUCTION);
    }

    /**
     * Load time.
     * @param  bool $totalStringOnly
     * @return array|string
     */
    public function loadTime(bool $totalStringOnly = true)
    {
        $start = APP_START_TIME; $end = microtime(true);

        $total = $end - $start;
        $totalString = sprintf('%.5f', $total);
        if ($totalStringOnly) {
            return $totalString;
        }

        return [$start, $end, $total, $totalString];
    }

    /**
     * Set service.
     * @param  froq\service\Service $service
     * @return void
     */
    public function setService(Service $service): void
    {
        $this->service = $service;
    }

    /**
     * Set service method.
     * @param  string $method
     * @return void
     */
    public function setServiceMethod(string $method): void
    {
        $this->service && $this->service->setMethod($method);
    }

    /**
     * Call service method (for internal service method calls).
     * @param  string      $call
     * @param  array|null  $callArgs
     * @param  bool        $prepareMethod
     * @return any
     * @throws froq\AppException
     */
    public function callServiceMethod(string $call, array $callArgs = null, bool $prepareMethod = false)
    {
        @ [$className, $classMethod] = explode('::', $call);
        if (!isset($className, $classMethod)) {
            throw new AppException('Both service class name & method are required');
        }

        $className = ServiceFactory::toServiceName($className);
        if ($prepareMethod) {
            $classMethod = ServiceFactory::toServiceMethod($classMethod);
        }
        $class = ServiceFactory::toServiceClass($className);
        $classFile = ServiceFactory::toServiceFile($className);

        if (!file_exists($classFile)) {
            throw new AppException("Service class file '{$classFile}' not found");
        }
        if (!class_exists($class)) {
            throw new AppException("Service class '{$class}' not found");
        }
        if (!method_exists($class, $classMethod)) {
            throw new AppException("Service class method '{$classMethod}' not found");
        }

        // keep current service
        $service = $this->service;

        // overrides also in service constructor (so, get_service() etc. should have accurate service info)
        $this->service = new $class($this, $className, $classMethod, $callArgs ?? [], $service);

        $output = $this->service->run(false);

        // restore current service
        $this->service = $service;

        return $output;
    }

    /**
     * Apply config.
     * @param  array $config
     * @return void
     */
    private function applyConfig(array $config): void
    {
        // override
        if ($this->config != null) {
            $config = Config::merge($config, $this->config->getData());
        }
        $this->config = new Config($config);

        // set/reset logger options
        $loggerOptions = $this->config->get('logger');
        if ($loggerOptions != null) {
            isset($loggerOptions['level']) && $this->logger->setLevel($loggerOptions['level']);
            isset($loggerOptions['directory']) && $this->logger->setDirectory($loggerOptions['directory']);
        }
    }

    /**
     * Apply defaults.
     * @return void
     */
    private function applyDefaults(): void
    {
        $timezone = $this->config->get('timezone');
        if ($timezone != null) {
            date_default_timezone_set($timezone);
        }

        $encoding = $this->config->get('encoding');
        if ($encoding != null) {
            mb_internal_encoding($encoding);
            ini_set('default_charset', $encoding);
        }

        $locales = $this->config->get('locales');
        if ($locales != null) {
            foreach ((array) $locales as $name => $value) {
                setlocale($name, $value);
            }
        }
    }

    /**
     * Start output buffer.
     * @return void
     */
    private function startOutputBuffer(): void
    {
        ob_start();
        ob_implicit_flush(0);
        ini_set('implicit_flush', 'Off');
    }

    /**
     * End output buffer.
     * @param  any $output
     * @return void
     */
    private function endOutputBuffer($output = null): void
    {
        // handle redirections
        $statusCode = $this->response->status()->getCode();
        if ($statusCode >= 300 && $statusCode <= 399) {
            // clean & turn off output buffering
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->response->body()->setContentType('none');
        }
        // handle outputs
        else {
            // service methods use echo/print/view() then return 'null'
            if ($output === null) {
                $output = '';
                while (ob_get_level()) {
                    $output .= ob_get_clean();
                }
            }

            // call user output handler if provided
            if ($this->events->has('app.output')) {
                $output = $this->events->fire('app.output', $output);
            }

            $this->response->setBody($output);
        }

        // load time
        $exposeAppLoadTime = $this->config('exposeAppLoadTime');
        if ($exposeAppLoadTime === true || $exposeAppLoadTime === $this->env()) {
            $this->response->header('X-App-Load-Time', $this->loadTime());
        }

        $this->response->end();
    }

    /**
     * Halt.
     * @param  string $status
     * @param  string $reason
     * @return void
     */
    private function halt(string $status, string $reason): void
    {
        header('HTTP/1.0 '. $status);
        header('Connection: close');
        header('Content-Type: none');

        $xHaltMessage = sprintf('X-Halt: Reason=%s, Ip=%s, Url:%s', $reason,
            Util::getClientIp(), Util::getCurrentUrl());

        header($xHaltMessage);
        header_remove('X-Powered-By');

        $this->logger->logFail(new AppException($xHaltMessage));

        print '<!---->';

        exit(1); // boom!
    }

    /**
     * Halt check (for safety & security).
     * @return ?array
     */
    private function haltCheck(): ?array
    {
        // built-in http server
        if (PHP_SAPI == 'cli-server') {
            return null;
        }

        // check client host
        $hosts = $this->config->get('hosts');
        if ($hosts != null && (empty($_SERVER['HTTP_HOST']) ||
            !in_array($_SERVER['HTTP_HOST'], (array) $hosts))) {
            return ['hosts', '400 Bad Request'];
        }

        @ ['maxRequest' => $maxRequest,
           'allowEmptyUserAgent' => $allowEmptyUserAgent,
           'allowFileExtensionSniff' => $allowFileExtensionSniff] = $this->config->get('security');

        // check request count
        if ($maxRequest != null && count($_REQUEST) > $maxRequest) {
            return ['maxRequest', '429 Too Many Requests'];
        }

        // check user agent
        if ($allowEmptyUserAgent === false && (empty($_SERVER['HTTP_USER_AGENT']) ||
            trim($_SERVER['HTTP_USER_AGENT']) == '')) {
            return ['allowEmptyUserAgent', '400 Bad Request'];
        }

        // check file extension
        if ($allowFileExtensionSniff === false &&
            preg_match('~\.(?:p[hyl]p?|rb|cgi|cf[mc]|p(?:pl|lx|erl)|aspx?)$~i',
                (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            return ['allowFileExtensionSniff', '400 Bad Request'];
        }

        // check service load
        $loadAvg = $this->config->get('loadAvg');
        if ($loadAvg != null && sys_getloadavg()[0] > $loadAvg) {
            return ['loadAvg', '503 Service Unavailable'];
        }

        return null;
    }
}
