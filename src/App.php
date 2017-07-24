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

use Froq\Event\Events;
use Froq\Config\Config;
use Froq\Logger\Logger;
use Froq\Database\Database;
use Froq\Http\{Http, Request, Response};
use Froq\Util\Traits\{SingleTrait, GetterTrait};
use Froq\Service\{Service, ServiceAdapter, ServiceInterface};

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
     * Getter trait.
     * @object Froq\Util\Traits\GetterTrait
     */
    use GetterTrait;

    /**
     * App envs.
     * @const string
     */
    const ENV_DEV        = 'dev',
          ENV_STAGE      = 'stage',
          ENV_PRODUCTION = 'production';

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
     * Logger.
     * @var Froq\Logger\Logger
     */
    private $logger;

    /**
     * Config.
     * @var Froq\Config\Config
     */
    private $config;

    /**
     * Events.
     * @var Froq\Events\Events
     */
    private $events;

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
     * Service.
     * @var Froq\Service\Service
     */
    private $service;

    /**
     * Database.
     * @var Froq\Database\Database
     */
    private $database;

    /**
     * Constructor.
     * @param array $config
     */
    final private function __construct(array $config)
    {
        if (!defined('APP_DIR')) {
            throw new AppException('Application directory is not defined!');
        }

        $this->logger = new Logger();

        // set default config first
        $this->setConfig($config);

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

        $this->database = new Database($this);
        $this->events   = new Events();
        $this->request  = new Request();
        $this->response = new Response();
    }

    /**
     * Destructor.
     */
    final public function __destruct()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Get database.
     * @return Froq\Database\Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Run.
     * @return void
     */
    final public function run()
    {
        // security & performans checks
        if ($halt = $this->haltCheck()) {
            $this->halt($halt);
        }

        // re-set global app var (could be modified by user config)
        set_global('app', $this);

        $this->setDefaults();

        $this->request->init(['uriRoot' => $this->root]);
        $this->response->init();

        $this->startOutputBuffer();

        $this->service = (new ServiceAdapter($this))->getService();

        // here!
        $this->events->fire('service.beforeRun');
        $output = $this->service->run();
        $this->events->fire('service.afterRun');

        $this->endOutputBuffer($output);
    }

    /**
     * Start output buffer.
     * @return void
     */
    final public function startOutputBuffer()
    {
        ini_set('implicit_flush', 'Off');

        $gzipOptions = $this->config->get('app.gzip');
        if ($gzipOptions) {
            if (!headers_sent()) {
                ini_set('zlib.output_compression', 'Off');
            }

            // detect client gzip status
            $acceptEncoding = $this->request->headers->get('Accept-Encoding');
            if ($acceptEncoding && strpos($acceptEncoding, 'gzip') !== false) {
                $this->response->setGzipOptions($gzipOptions);
            }
        }

        // start!
        ob_start();
    }

    /**
     * End output buffer.
     * @param  any $output
     * @return void
     */
    final public function endOutputBuffer($output = null)
    {
        // handle redirections
        $statusCode = $this->response->status->getCode();
        if ($statusCode >= 300 && $statusCode <= 399) {
            // clean & turn off output buffering
            while (ob_get_level()) {
                ob_end_clean();
            }
            // no content!
            $this->response->setContentType('none');
        }
        // handle outputs
        else {
            // print'ed service methods return "null"
            if ($output === null) {
                $output = '';
                while (ob_get_level()) {
                    $output .= ob_get_clean();
                }
            }

            // use user output handler if provided
            if ($this->events->has('app.output')) {
                $output = $this->events->fire('app.output', $output);
            }

            // set response body
            $this->response->setBody($output);
        }

        // send response cookies, headers and body
        $this->response->sendHeaders();
        $this->response->sendCookies();
        $this->response->send();
    }


    /**
     * Set env.
     * @param  string $env
     * @return self
     */
    final public function setEnv(string $env): self
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     * @return string
     */
    final public function getEnv(): string
    {
        return $this->env;
    }

    /**
     * Set root.
     * @param  string $root
     * @return self
     */
    final public function setRoot(string $root): self
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     * @return string
     */
    final public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Set config.
     * @param  array $config
     * @return self
     */
    final public function setConfig(array $config): self
    {
        // overwrite
        if ($this->config) {
            $config = Config::merge($config, $this->config->getData());
        }
        $this->config = new Config($config);

        // set/reset log options
        if ($logOpts = $this->config['app.logger']) {
            isset($logOpts['level']) && $this->logger->setLevel($logOpts['level']);
            isset($logOpts['directory']) && $this->logger->setDirectory($logOpts['directory']);
        }

        return $this;
    }

    /**
     * Get config.
     * @return Froq\Config\Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get config value.
     * @param  string $key
     * @param  any    $valueDefault
     * @return any
     */
    final public function getConfigValue(string $key, $valueDefault = null)
    {
        return $this->config->get($key, $valueDefault);
    }

    /**
     * Set defaults.
     * @return self
     */
    final public function setDefaults(): self
    {
        $locale   = $this->config->get('app.locale');
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
            }
            mb_internal_encoding($encoding);
        }

        return $this;
    }

    /**
     * Internal service method call.
     * @param  string $call
     * @param  array  $arguments
     * @return any
     */
    final public function callServiceMethod(string $call, array $arguments = [])
    {
        @ list($className, $classMethod) = explode('::', $call);
        if (!isset($className, $classMethod)) {
            throw new AppException('Both service class & method (Class::method) names are required!');
        }

        $className = ServiceInterface::NAMESPACE . $className;

        // return service method call
        return call_user_func_array([new $className($this), $classMethod], $arguments);
    }

    /**
     * Check app env is development.
     * @return bool
     */
    final public function isDev(): bool
    {
        return ($this->env == self::ENV_DEV);
    }

    /**
     * Check app env is stage.
     * @return bool
     */
    final public function isStage(): bool
    {
        return ($this->env == self::ENV_STAGE);
    }

    /**
     * Check app env is production.
     * @return bool
     */
    final public function isProduction(): bool
    {
        return ($this->env == self::ENV_PRODUCTION);
    }

    /**
     * Load time.
     * @return array
     */
    final public function loadTime(): array
    {
        $start = APP_START_TIME; $end = microtime(true);

        return ['start' => $start, 'end' => $end, 'total' => ($end - $start)];
    }

    /**
     * Halt app run.
     * @param  string $status
     * @return void
     */
    final private function halt(string $status)
    {
        header(sprintf('%s %s', Http::detectVersion(), $status));
        header('Connection: close');
        header('Content-Type: none');
        header('Content-Length: 0');
        header_remove('X-Powered-By');
        exit(1);
    }

    /**
     * Halt check for security & safety.
     * @return string
     */
    final private function haltCheck(): string
    {
        // check client host
        $hosts = $this->config['app.hosts'];
        if ($hosts && (!isset($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], $hosts))) {
            return '400 Bad Request';
        }

        @ list($maxRequest, $allowEmptyUserAgent, $allowFileExtensionSniff) = $this->config['app.security'];

        // check request count
        if (isset($maxRequest) && count($_REQUEST) > $maxRequest) {
            return '429 Too Many Requests';
        }

        // check user agent
        if (isset($allowEmptyUserAgent) && $allowEmptyUserAgent === false
            && (!isset($_SERVER['HTTP_USER_AGENT']) || !trim($_SERVER['HTTP_USER_AGENT']))) {
            return '400 Bad Request';
        }

        // check file extension
        if (isset($allowFileExtensionSniff) && $allowFileExtensionSniff === false
            && preg_match('~\.(p[hyl]p?|rb|cgi|cf[mc]|p(pl|lx|erl)|aspx?)$~i',
                    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            return '400 Bad Request';
        }

        // check service load
        $loadAvg = $this->config['app.loadAvg'];
        if ($loadAvg && sys_getloadavg()[0] > $loadAvg) {
            return '503 Service Unavailable';
        }

        return '';
    }
}
