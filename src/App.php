<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq;

use Froq\Util\{Util, Traits\SingleTrait};
use Froq\Event\Events;
use Froq\Config\Config;
use Froq\Logger\Logger;
use Froq\Database\Database;
use Froq\Http\{Http, Request, Response};
use Froq\Service\{Service, ServiceFactory};

/**
 * @package Froq
 * @object  Froq\App
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class App
{
    /**
     * Single trait.
     * @object Froq\Util\Traits\SingleTrait
     */
    use SingleTrait;

    /**
     * App envs.
     * @const string
     */
    public const ENV_DEV          = 'dev',
                 ENV_STAGE        = 'stage',
                 ENV_PRODUCTION   = 'production';

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
     * @var Froq\Config\Config
     */
    private $config;

    /**
     * Logger.
     * @var Froq\Logger\Logger
     */
    private $logger;

    /**
     * Events.
     * @var Froq\Events\Events
     */
    private $events;

    /**
     * Service.
     * @var Froq\Service\Service
     */
    private $service;

    /**
     * Request.
     * @var Froq\Http\Request
     */
    private $request;

    /**
     * Response.
     * @var Froq\Http\Response
     */
    private $response;

    /**
     * Db.
     * @var Froq\Database\Database
     */
    private $db;

    /**
     * Constructor.
     * @param array $config
     */
    private function __construct(array $config)
    {
        if (!defined('APP_DIR')) {
            throw new AppException('Application directory is not defined!');
        }

        $this->logger = new Logger();

        // set default config first
        $this->applyConfig($config);

        // set app as global (@see app() function)
        set_global('app', $this);

        // load core app globals
        if (is_file($file = APP_DIR .'/app/global/def.php')) {
            include($file);
        }
        if (is_file($file = APP_DIR .'/app/global/fun.php')) {
            include($file);
        }

        // set handlers
        set_error_handler(require(__dir__ .'/handler/error.php'));
        set_exception_handler(require(__dir__ .'/handler/exception.php'));
        register_shutdown_function(require(__dir__ .'/handler/shutdown.php'));

        $this->db = new Database($this);
        $this->events = new Events();
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
     * @return Froq\Config\Config
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
     * @return Froq\Logger\Logger
     */
    public function logger(): Logger
    {
        return $this->logger;
    }

    /**
     * Events.
     * @return Froq\Events\Events
     */
    public function events(): Events
    {
        return $this->events;
    }

    /**
     * Service.
     * @return ?Froq\Service\Service
     */
    public function service(): ?Service
    {
        return $this->service;
    }

    /**
     * Request.
     * @return ?Froq\Http\Request
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Response.
     * @return ?Froq\Http\Response
     */
    public function response(): ?Response
    {
        return $this->response;
    }

    /**
     * Db.
     * @return Froq\Database\Database
     */
    public function db(): Database
    {
        return $this->db;
    }

    /**
     * Run.
     * @param  array options
     * @return void
     */
    public function run(array $options): void
    {
        // apply user options (pub/index.php)
        if (isset($options['env'])) $this->env = $options['env'];
        if (isset($options['root'])) $this->root = $options['root'];
        if (isset($options['config'])) $this->applyConfig($options['config']);

        // keep globals clean..
        unset($GLOBALS['app'], $GLOBALS['appEnv'], $GLOBALS['appRoot'], $GLOBALS['appConfig']);

        // check env
        if (empty($this->env)) {
            throw new AppException('Application env is not defined!');
        }

        // security & performans checks
        $halt = $this->haltCheck();
        if ($halt != null) {
            $this->halt($halt[1], $halt[0]);
        }

        $this->applyDefaults();

        $this->request = new Request($this);
        $this->response = new Response($this);

        // @overwrite
        set_global('app', $this);

        $this->startOutputBuffer();

        // create service
        $this->service = ServiceFactory::create($this);
        if ($this->service == null) {
            throw new AppException('Could not create service!');
        }

        // here!!
        $this->events->fire('service.beforeRun');
        $output = $this->service->run();
        $this->events->fire('service.afterRun');

        $this->endOutputBuffer($output);
    }

    /**
     * Start output buffer.
     * @return void
     */
    public function startOutputBuffer(): void
    {
        ini_set('implicit_flush', 'Off');

        $gzipOptions = $this->config->get('app.gzip');
        if ($gzipOptions) {
            if (!headers_sent()) {
                ini_set('zlib.output_compression', 'Off');
            }

            // detect client gzip status
            $acceptEncoding = $this->request->getHeader('Accept-Encoding');
            if ($acceptEncoding && strpos($acceptEncoding, 'gzip') !== false) {
                $this->response->setGzipOptions($gzipOptions);
            }
        }

        ob_start();
    }

    /**
     * End output buffer.
     * @param  any $output
     * @return void
     */
    public function endOutputBuffer($output = null): void
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
            // echo or print'ed service methods return "null"
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
     * Apply config.
     * @param  array $config
     * @return self
     */
    public function applyConfig(array $config): self
    {
        // overwrite
        if ($this->config) {
            $config = Config::merge($config, $this->config->getData());
        }
        $this->config = new Config($config);

        // set/reset log options
        if ($logOptions = $this->config['app.logger']) {
            isset($logOptions['level']) && $this->logger->setLevel($logOptions['level']);
            isset($logOptions['directory']) && $this->logger->setDirectory($logOptions['directory']);
        }

        return $this;
    }

    /**
     * Apply defaults.
     * @return self
     */
    public function applyDefaults(): self
    {
        $locale = $this->config->get('app.locale');
        $encoding = $this->config->get('app.encoding');
        $timezone = $this->config->get('app.timezone');

        if ($timezone) {
            date_default_timezone_set($timezone);
        }

        if ($encoding) {
            ini_set('default_charset', $encoding);
            if ($locale) {
                $locale = sprintf('%s.%s', $locale, $encoding);
                setlocale(LC_TIME, $locale);
                setlocale(LC_NUMERIC, $locale);
                setlocale(LC_MONETARY, $locale);
                setlocale(LC_COLLATE, $locale);
            }
            mb_internal_encoding($encoding);
        }

        return $this;
    }

    /**
     * Call service method (for internal service method calls).
     * @param  string $call
     * @param  array  $arguments
     * @return any
     */
    public function callServiceMethod(string $call, array $arguments = [])
    {
        @ [$className, $classMethod] = explode('::', $call);
        if (!isset($className, $classMethod)) {
            throw new AppException('Both service class & method are required!');
        }

        $className = Service::NAMESPACE . $className;

        // return service method call
        return call_user_func_array([new $className($this), $classMethod], $arguments);
    }

    /**
     * Is dev.
     * @return bool
     */
    public function isDev(): bool
    {
        return ($this->env == self::ENV_DEV);
    }

    /**
     * Is stage.
     * @return bool
     */
    public function isStage(): bool
    {
        return ($this->env == self::ENV_STAGE);
    }

    /**
     * Is production.
     * @return bool
     */
    public function isProduction(): bool
    {
        return ($this->env == self::ENV_PRODUCTION);
    }

    /**
     * Load time.
     * @return array
     */
    public function loadTime(): array
    {
        $start = APP_START_TIME; $end = microtime(true);

        return ['start' => $start, 'end' => $end, 'total' => ($end - $start)];
    }

    /**
     * Halt.
     * @param  string $status
     * @param  string $reason
     * @return void
     */
    private function halt(string $status, string $reason): void
    {
        header(sprintf('%s %s', Http::detectVersion(), $status));
        header('Connection: close');
        header('Content-Type: none');
        header('Content-Length: 0');

        $xHaltMessage = sprintf('X-Halt: true, Reason=%s, Ip=%s', $reason, Util::getClientIp());
        try {
            throw new AppException($xHaltMessage);
        } catch (AppException $e) {
            $this->logger->logFail($e);
        }

        header($xHaltMessage);
        header_remove('X-Powered-By');

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

        // check if client host is allowed
        $hosts = $this->config['app.hosts'];
        if ($hosts && (empty($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], (array) $hosts))) {
            return ['hosts', '400 Bad Request'];
        }

        @ ['maxRequest' => $maxRequest,
           'allowEmptyUserAgent' => $allowEmptyUserAgent,
           'allowFileExtensionSniff' => $allowFileExtensionSniff] = $this->config['app.security'];

        // check request count
        if ($maxRequest && count($_REQUEST) > $maxRequest) {
            return ['maxRequest', '429 Too Many Requests'];
        }

        // check user agent
        if ($allowEmptyUserAgent === false
            && (empty($_SERVER['HTTP_USER_AGENT']) || trim($_SERVER['HTTP_USER_AGENT']) == '')) {
            return ['allowEmptyUserAgent', '400 Bad Request'];
        }

        // check file extension
        if ($allowFileExtensionSniff === false
            && preg_match('~\.(?:p[hyl]p?|rb|cgi|cf[mc]|p(?:pl|lx|erl)|aspx?)$~i',
                (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            return ['allowFileExtensionSniff', '400 Bad Request'];
        }

        // check service load
        $loadAvg = $this->config['app.loadAvg'];
        if ($loadAvg && sys_getloadavg()[0] > $loadAvg) {
            return ['loadAvg', '503 Service Unavailable'];
        }

        return null;
    }
}
