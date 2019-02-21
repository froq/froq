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

        $this->db = new Database($this);
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
     * Call magic.
     * @param  string     $method
     * @param  array|null $methodArguments
     * @return any
     * @throws froq\AppException
     * @since  3.0
     */
    public function __call(string $method, array $methodArguments = null)
    {
        // this is a getter method actually
        if (strpos($method, 'get') !== 0) {
            throw new AppException("Only 'get' prefixed methods accepted for __call() method");
        }

        $name = lcfirst(substr($method, 3));
        // just an exception
        if ($name == 'database') {
            return $this->db;
        }
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new AppException("Undefined property name '{$name}' given");
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
     * @return froq\config\Config
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Config value.
     * @param  string $key
     * @param  any    $valueDefault
     * @return any
     */
    public function configValue(string $key, $valueDefault = null)
    {
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
     * Db.
     * @return froq\database\Database
     */
    public function db(): Database
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

        // @override
        set_global('app', $this);

        // create service
        $this->service = ServiceFactory::create($this);
        if ($this->service == null) {
            throw new AppException('Could not create service');
        }

        $this->startOutputBuffer();

        // here!!
        $this->events->fire('service.beforeRun');
        $output = $this->service->run();
        $this->events->fire('service.afterRun');

        $this->endOutputBuffer($output);
    }

    /**
     * Is dev.
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->env == self::ENV_DEV;
    }

    /**
     * Is stage.
     * @return bool
     */
    public function isStage(): bool
    {
        return $this->env == self::ENV_STAGE;
    }

    /**
     * Is production.
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->env == self::ENV_PRODUCTION;
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
     * Call service method (for internal service method calls).
     * @param  string $call
     * @param  array  $arguments
     * @return any
     * @throws froq\AppException
     */
    public function callServiceMethod(string $call, array $arguments = [])
    {
        @ [$className, $classMethod] = explode('::', $call);
        if (!isset($className, $classMethod)) {
            throw new AppException('Both service class & method are required');
        }

        $className = Service::NAMESPACE . $className;

        // return service method call
        return call_user_func_array([new $className($this), $classMethod], $arguments);
    }

    /**
     * Apply config.
     * @param  array $config
     * @return void
     */
    private function applyConfig(array $config): void
    {
        static $noMerge = ['locales'];

        // override
        if ($this->config != null) {
            $newConfig = $config; $oldConfig = $this->config->getData();
            foreach ($noMerge as $key) {
                if (isset($newConfig[$key])) {
                    unset($oldConfig[$key]);
                }
            }
            $config = Config::merge($newConfig, $oldConfig);
        }
        $this->config = new Config($config);

        // set/reset log options
        $logOptions = $this->config->get('logger');
        if ($logOptions != null) {
            isset($logOptions['level']) && $this->logger->setLevel($logOptions['level']);
            isset($logOptions['directory']) && $this->logger->setDirectory($logOptions['directory']);
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
            foreach ((array) $locales as $category => $locale) {
                setlocale($category, $locale);
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
            $this->response->setContentType('none');
        }
        // handle outputs
        else {
            // echo'd or print'ed service methods return 'null'
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
