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

namespace froq\app;

use froq\app\{AppException, Env};
use froq\event\Events;
use froq\config\Config;
use froq\logger\Logger;
use froq\session\Session;
use froq\database\Database;
use froq\http\{Http, Request, Response};
use froq\service\{ServiceFactory, ServiceInterface};
use froq\traits\SingletonTrait;
use froq\util\Util;
use Throwable;

/**
 * App.
 * @package froq\app
 * @object  froq\app\App
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class App
{
    /**
     * Singleton trait.
     * @object froq\traits\SingletonTrait
     */
    use SingletonTrait;

    /**
     * Root (provides options like "app.local/v1/book/1" for versioning etc.).
     * @var string
     */
    private string $root = '/';

    /**
     * Dir.
     * @var string
     */
    private string $dir;

    /**
     * Env.
     * @var froq\app\Env
     */
    private Env $env;

    /**
     * Config.
     * @var froq\config\Config
     */
    private Config $config;

    /**
     * Logger.
     * @var froq\logger\Logger
     */
    private Logger $logger;

    /**
     * Events.
     * @var froq\events\Events
     */
    private Events $events;

    /**
     * Request.
     * @var ?froq\http\Request
     */
    private ?Request $request;

    /**
     * Response.
     * @var ?froq\http\Response
     */
    private ?Response $response;

    /**
     * Session.
     * @var ?froq\session\Session
     */
    private ?Session $session;

    /**
     * Database.
     * @var ?froq\database\Database
     */
    private ?Database $database;

    /**
     * Service.
     * @var ?froq\service\ServiceInterface
     */
    private ?ServiceInterface $service;

    /**
     * Called service.
     * @var ?froq\service\ServiceInterface
     * @since 4.0
     */
    private ?ServiceInterface $calledService;

    /**
     * Caller service.
     * @var ?froq\service\ServiceInterface
     * @since 4.0
     */
    private ?ServiceInterface $callerService;

    /**
     * Constructor.
     * @param  array $configs
     * @throws froq\app\AppException
     */
    private function __construct(array $configs)
    {
        // App dir is required (@see skeleton/pub/index.php).
        if (!defined('APP_DIR')) {
            throw new AppException('APP_DIR is not defined');
        }

        [$this->dir, $this->env, $this->config, $this->logger, $this->events] = [
            APP_DIR, new Env(), new Config(), new Logger(), new Events()];

        // Set default configs first.
        $this->applyConfigs($configs);

        // Set app as global (@see app()).
        set_global('app', $this);

        // Load app globals if exists.
        if (file_exists($file = $this->dir .'/app/global/def.php')) {
            include $file;
        }
        if (file_exists($file = $this->dir .'/app/global/fun.php')) {
            include $file;
        }

        // Set handlers.
        set_error_handler(include __dir__ .'/handler/error.php');
        set_exception_handler(include __dir__ .'/handler/exception.php');
        register_shutdown_function(include __dir__ .'/handler/shutdown.php');
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
     * Clone.
     * @return void
     * @since  4.0
     */
    public function __clone()
    {
        $this->calledService = $this->callerService = null;
    }

    /**
     * Call.
     * @param  string     $method
     * @param  array|null $methodArgs
     * @return any
     * @since  4.0
     */
    public function __call(string $method, array $methodArgs = null)
    {
        static $names = ['root', 'dir', 'env', 'config', 'logger', 'events', 'request',
            'response', 'session', 'database', 'service', 'calledService', 'callerService'];

        $name = lcfirst(substr($method, 3));
        if (in_array($name, $names)) {
            return $this->{$name}(...$methodArgs);
        }

        throw new AppException(sprintf('Invalid call as App.%s(), valid are only %s with get'.
            ' prefix', $method, join(', ', array_map('ucfirst', $names))));
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
     * Dir.
     * @return string
     */
    public function dir(): string
    {
        return $this->dir;
    }

    /**
     * Env.
     * @return froq\app\Env
     */
    public function env(): Env
    {
        return $this->env;
    }

    /**
     * Runtime.
     * @return float
     * @since  4.0 Replaced with loadTime().
     */
    public function runtime(): float
    {
        return round(microtime(true) - APP_START_TIME, 4);
    }

    /**
     * Config.
     * @param  string|array|null $key
     * @param  any|null          $valueDefault
     * @return any|null|froq\config\Config
     * @throws froq\app\AppException If ket type not valid.
     */
    public function config($key = null, $valueDefault = null)
    {
        // Set is not allowed, so config readonly and set available in cfg.php files only.
        if ($key === null) {
            return $this->config;
        }
        if (is_string($key)) {
            return $this->config->get($key, $valueDefault);
        }
        if (is_array($key)) {
            return $this->config->getAll($key, $valueDefault);
        }

        throw new AppException(sprintf('Only string, array and null keys allowed for %s() '.
            'method, %s given', __method__, gettype($key)));
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
     * Request.
     * @return ?froq\http\Request
     */
    public function request(): ?Request
    {
        return ($this->request ?? null);
    }

    /**
     * Response.
     * @return ?froq\http\Response
     */
    public function response(): ?Response
    {
        return ($this->response ?? null);
    }

    /**
     * Session.
     * @return ?froq\session\Session
     * @since  3.18
     */
    public function session(): ?Session
    {
        return ($this->session ?? null);
    }

    /**
     * Database.
     * @return ?froq\database\Database
     * @since  4.0
     */
    public function database(): ?Database
    {
        return ($this->database ?? null);
    }

    /**
     * Db.
     * @aliasOf database()
     */
    public function db()
    {
        return $this->database();
    }

    /**
     * Service.
     * @return ?froq\service\ServiceInterface
     */
    public function service(): ?ServiceInterface
    {
        return ($this->service ?? null);
    }

    /**
     * Get called service.
     * @return ?froq\service\ServiceInterface
     * @since  4.0
     */
    public function calledService(): ?ServiceInterface
    {
        return ($this->calledService ?? null);
    }

    /**
     * Get caller service.
     * @return ?froq\service\ServiceInterface
     * @since  4.0
     */
    public function callerService(): ?ServiceInterface
    {
        return ($this->callerService ?? null);
    }

    /**
     * Run.
     * @param  array $options
     * @return void
     * @throws froq\app\AppException
     */
    public function run(array $options = null): void
    {
        // Apply run options (user options) (@see skeleton/pub/index.php).
        ['root' => $root, 'env' => $env, 'configs' => $configs] = $options;

        $root    && $this->root = $root;
        $env     && $this->env()->setName($env);
        $configs && $this->applyConfigs($configs);

        if ($this->root == '' || $this->env->getName() == '') {
            throw new AppException('App env or root cannot be empty');
        }

        // Security & performans checks.
        $halt = $this->haltCheck();
        if ($halt != null) {
            $this->halt($halt[1], $halt[0]);
        }

        // Apply defaults (timezone, locales etc.)
        $this->applyDefaults();

        $this->request = new Request($this);
        $this->response = new Response($this);

        // These options could be emptied by developer to disable session or database with 'null'
        // if app won't be using session & database.
        [$session, $database] = $this->config->getAll(['session', 'database']);

        isset($session) && $this->session = new Session((array) $session);
        isset($database) && $this->database = new Database($this);

        // @override
        set_global('app', $this);

        // Create service.
        $this->service = ServiceFactory::create($this);
        if ($this->service == null) {
            throw new AppException('Failed to create service');
        }

        $this->startOutputBuffer();

        // Here!!
        $this->events->fire('service.beforeRun');
        $return = $this->service->serve();
        $this->events->fire('service.afterRun');

        $this->endOutputBuffer($return);
    }

    /**
     * Is root.
     * @return bool
     */
    public function isRoot(): bool
    {
        return ($this->root == $this->request->uri()->get('path'));
    }

    /**
     * Call service (for internal service calls).
     * @param  string      $call
     * @param  array|null  $callArgs
     * @param  bool        $prepareMethod
     * @return any
     * @throws froq\app\AppException
     */
    public function callService(string $call, array $callArgs = null, bool $prepareMethod = false)
    {
        @ [$className, $classMethod] = explode('.', $call);
        if ($className == null) {
            throw new AppException('Both service class name & method are required');
        }

        $className = ServiceFactory::toServiceName($className);
        $classMethod = $classMethod ?? ServiceInterface::METHOD_MAIN;
        if ($prepareMethod) {
            $classMethod = ServiceFactory::toServiceMethod($classMethod);
        }

        $class = ServiceFactory::toServiceClass($className);
        $classFile = ServiceFactory::toServiceFile($className);

        if (!file_exists($classFile)) {
            throw new AppException(sprintf('Service class file %s not found', $classFile));
        } elseif (!class_exists($class)) {
            throw new AppException(sprintf('Service class %s not found', $class));
        } elseif (!method_exists($class, $classMethod)) {
            throw new AppException(sprintf('Service class method %s not found', $classMethod));
        }

        $service = new $class($this, $className, $classMethod, $callArgs);

        // Store called & caller service, so both could be used some app services for
        // detecting which service called or caller at the moment (at serve time).
        $this->calledService = $service;
        $this->callerService = $this->service;

        $return = $service->serve();

        return $return;
    }

    /**
     * Apply configs.
     * @param  array $configs
     * @return void
     */
    private function applyConfigs(array $configs): void
    {
        $this->config->update($configs);

        // Set/reset logger options.
        @ ['level' => $level, 'directory' => $directory] = $this->config->get('logger');

        $level && $this->logger->setOption('level', $level);
        $directory && $this->logger->setOption('directory', $directory);
    }

    /**
     * Apply defaults.
     * @return void
     */
    private function applyDefaults(): void
    {
        [$timezone, $encoding, $locales] = $this->config->getAll(['timezone', 'encoding', 'locales']);

        if ($timezone != null) {
            date_default_timezone_set($timezone);
        }

        if ($encoding != null) {
            ini_set('default_charset', $encoding);
            ini_set('internal_encoding', $encoding);
        }

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
        ini_set('implicit_flush', 'off');
    }

    /**
     * End output buffer.
     * @param  any       $output
     * @param  bool|null $isError @internal (@see Message.setBody())
     * @return void
     */
    private function endOutputBuffer($output, bool $isError = null): void
    {
        $response = $this->response();
        if ($response == null) {
            throw new AppException('App has no response yet');
        }

        // Handle redirections.
        $code = $response->getStatusCode();
        if ($code >= 300 && $code <= 399) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $response->setBody(null, ['type' => 'n/a']);
        }
        // Handle outputs & returns.
        else {
            $body = $response->getBody();
            $content = $body->getContent();
            $contentAttributes = $body->getContentAttributes();

            // Pass, output comes from App.error() already.
            if ($isError) {
            }
            // Service methods that use echo/print/view()/response.setBody()
            // will return null.
            elseif ($output === null) {
                $output = '';
                while (ob_get_level()) {
                    $output .= ob_get_clean();
                }
            }

            // Returned content from service method or set on body.
            $content = $content ?: $output;

            // Call user output handler if provided.
            if ($this->events->has('app.output')) {
                $content = $this->events->fire('app.output', $content);
            }

            $response->setBody($content, $contentAttributes, $isError);
        }

        $exposeAppRuntime = $this->config('exposeAppRuntime');
        if ($exposeAppRuntime === true || $exposeAppRuntime === $this->env()->getName()) {
            $response->setHeader('X-App-Runtime', sprintf('%.4f', $this->runtime()));
        }

        // The end..
        $response->end();
    }

    /**
     * Error.
     * @param  Throwable $error
     * @param  bool      $log
     * @return void
     * @since  4.0
     */
    public function error(Throwable $error, bool $log = true): void
    {
        $log && $this->errorLog($error);

        $code = 500;
        $response = $this->response();

        // Status may change in FailService.
        $response && $response->setStatusCode($code);

        // Call user error handler if provided.
        $this->events->fire('app.error', $error);

        // Clear outputs (FailService will work below for output).
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_start();
        $return = $this->callService('FailService', ['code' => $code]);
        $output = ob_get_clean();

        $output = $return ?? $output;

        // Prepend error top of the output (if ini.display_errors is on).
        if ($output == null || is_string($output)) {
            $outputErrors = ini('display_errors', '', true);
            if ($outputErrors) {
                $output = $error ."\n". $output;
            }
        }

        $this->endOutputBuffer($output, true);
    }

    /**
     * Error log.
     * @param  any $error
     * @return void
     * @since  4.0
     */
    public function errorLog($error): void
    {
        $this->logger->logFail($error);
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
        header('Content-Type: n/a');
        header('Content-Length: 0');

        $xHaltMessage = sprintf('X-Halt: Reason=%s, Ip=%s, Url:%s', $reason,
            Util::getClientIp(), Util::getCurrentUrl());

        header($xHaltMessage);
        header_remove('X-Powered-By');

        $this->logger->logFail(new AppException($xHaltMessage));

        // Boom!
        exit(1);
    }

    /**
     * Halt check (for safety & security).
     * @return ?array
     */
    private function haltCheck(): ?array
    {
        if (PHP_SAPI == 'cli-server') {
            return null;
        }

        // Check client host.
        $hosts = $this->config->get('hosts');
        if ($hosts != null && (
            empty($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], (array) $hosts))) {
            return ['hosts', '400 Bad Request'];
        }

        @ ['maxRequest' => $maxRequest,
           'allowEmptyUserAgent' => $allowEmptyUserAgent,
           'allowFileExtensionSniff' => $allowFileExtensionSniff] = $this->config->get('security');

        // Check request count.
        if ($maxRequest != null && count($_REQUEST) > $maxRequest) {
            return ['maxRequest', '429 Too Many Requests'];
        }

        // Check user agent.
        if ($allowEmptyUserAgent === false && (
            empty($_SERVER['HTTP_USER_AGENT']) || trim($_SERVER['HTTP_USER_AGENT']) == '')) {
            return ['allowEmptyUserAgent', '400 Bad Request'];
        }

        // Check file (uri) extension.
        if ($allowFileExtensionSniff === false && (
            preg_match('~\.(?:p[hyl]p?|rb|cgi|cf[mc]|p(?:pl|lx|erl)|aspx?)$~i',
                (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)))) {
            return ['allowFileExtensionSniff', '400 Bad Request'];
        }

        // Check service load.
        $loadAvg = $this->config->get('loadAvg');
        if ($loadAvg != null && sys_getloadavg()[0] > $loadAvg) {
            return ['loadAvg', '503 Service Unavailable'];
        }

        return null;
    }
}
