<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use froq\{event\EventManager, session\Session, database\Database};
use froq\common\{trait\InstanceTrait, object\Config, object\Registry};
use froq\http\{Request, Response, HttpException, response\Status,
    exception\client\NotFoundException, exception\client\NotAllowedException};
use froq\cache\{Cache, CacheFactory};
use froq\logger\{Logger, LogLevel};
use Assert, Stringable, Throwable;

/**
 * Application class which is responsible with all logics;
 * - Creating needed object instances such as Logger, Router, Servicer or Session, Database etc. those used
 * all over the application cycle.
 * - Registering/unregistering error handlers, handling and logging errors.
 * - Applying user-defined configuration options and starting/ending output buffer.
 * - Adding routes via short methods such as `get()`, `post()` etc. or resolving routes from requested URI using
 * router object or errorizing when no route, route controller or method found.
 * - Creating controllers, dispatching and getting action returns, sending those returns response object.
 *
 * @package froq
 * @object  froq\App
 * @author  Kerem Güneş
 * @since   1.0
 */
final class App
{
    use InstanceTrait;

    /**
     * For versioning (eg: "app.host/v1/book/1").
     * @var string
     */
    public readonly string $root;

    /** @var string */
    public readonly string $env;

    /** @var string */
    public readonly string $dir;

    /** @var froq\logger\Logger */
    public readonly Logger $logger;

    /** @var froq\http\Request */
    public readonly Request $request;

    /** @var froq\http\Response */
    public readonly Response $response;

    /** @var froq\session\Session|null */
    public readonly Session|null $session;

    /** @var froq\database\Database|null */
    public readonly Database|null $database;

    /** @var froq\cache\Cache|null */
    public readonly Cache|null $cache;

    /** @var froq\event\EventManager */
    private EventManager $eventManager;

    /** @var froq\Router */
    private Router $router;

    /** @var froq\Servicer */
    private Servicer $servicer;

    /** @var froq\common\object\Config */
    private Config $config;

    /** @var froq\common\object\Register */
    private static Registry $registry;

    /**
     * Constructor.
     *
     * @throws froq\AppException
     */
    private function __construct()
    {
        // App dir is required (@see pub/index.php).
        defined('APP_DIR') || throw new AppException('APP_DIR not defined');

        $this->logger   = new Logger(['level' => LogLevel::ALL]);
        $this->request  = new Request($this);
        $this->response = new Response($this);

        [$this->dir, $this->eventManager, $this->router, $this->servicer, $this->config, self::$registry] = [
            APP_DIR, new EventManager($this), new Router(), new Servicer(), new Config(), new Registry()
        ];

        // Register app.
        self::$registry::set('@app', $this, false);

        // Register handlers.
        Handler::registerErrorHandler();
        Handler::registerExceptionHandler();
        Handler::registerShutdownHandler();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        Handler::unregisterErrorHandler();
        Handler::unregisterExceptionHandler();
    }

    /**
     * Check whether URI path is app root.
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return ($this->root == $this->request->getPath());
    }

    /**
     * Check whether environment is local that defined.
     *
     * @return bool
     * @since  5.0
     */
    public function isLocal(): bool
    {
        return defined('__local__') && __local__;
    }

    /**
     * Get runtime.
     *
     * @param  int  $precision
     * @param  bool $format
     * @return float|string
     * @since  4.0
     */
    public function runtime(int $precision = 3, bool $format = false): float|string
    {
        $runtime = round(microtime(true) - APP_START, $precision);

        return !$format ? $runtime : sprintf('%.*F', $precision, $runtime);
    }

    /**
     * Set/get a cache item, throw `AppException` if no cache object initiated.
     *
     * @param  string|int|array<string|int> $key
     * @param  mixed|null                   $value
     * @param  int|null                     $ttl
     * @return mixed
     * @throws froq\AppException
     * @since  4.10
     */
    public function cache(string|int|array $key, mixed $value = null, int $ttl = null): mixed
    {
        isset($this->cache) || throw new AppException(
            'No cache object initiated yet, be sure `cache` option is not empty in config'
        );

        return (
            func_num_args() === 1
                ? $this->cache->get($key)
                : $this->cache->set($key, $value, $ttl)
        );
    }

    /**
     * Delete a cache item or all by "*" as key, throw `AppException` if no cache object initiated.
     *
     * @param  string|int|array<string|int> $key
     * @return bool
     * @throws froq\AppException
     * @since  4.10
     */
    public function uncache(string|int|array $key): bool
    {
        isset($this->cache) || throw new AppException(
            'No cache object initiated yet, be sure `cache` option is not empty in config'
        );

        return ($key === '*') ? $this->cache->clear() : $this->cache->delete($key);
    }

    /**
     * Add an event.
     *
     * @param  string   $name
     * @param  callable $callback
     * @param  mixed ...$options
     * @return self
     * @since  6.0
     */
    public function on(string $name, callable $callback, mixed ...$options): self
    {
        $this->eventManager->add($name, $callback, ...$options);

        return $this;
    }

    /**
     * Remove an event.
     *
     * @param  string $name
     * @return self
     * @since  6.0
     */
    public function off(string $name): self
    {
        $this->eventManager->remove($name);

        return $this;
    }

    /**
     * Define a route with given HTTP method / methods.
     *
     * @param  string          $route
     * @param  string          $methods
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function route(string $route, string $methods, string|callable $call): self
    {
        $this->router->addRoute($route, $methods, $call);

        return $this;
    }

    /**
     * Define a route with GET method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function get(string $route, string|callable $call): self
    {
        return $this->route($route, 'GET', $call);
    }

    /**
     * Define a route with POST method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function post(string $route, string|callable $call): self
    {
        return $this->route($route, 'POST', $call);
    }

    /**
     * Define a route with PUT method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function put(string $route, string|callable $call): self
    {
        return $this->route($route, 'PUT', $call);
    }

    /**
     * Define a route with DELETE method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function delete(string $route, string|callable $call): self
    {
        return $this->route($route, 'DELETE', $call);
    }

    /**
     * Add/get a service.
     *
     * @param  string                $name
     * @param  object|callable|array $service
     * @return object|callable|null
     * @since  4.0
     */
    public function service(string $name, object|callable|array $service = null): object|callable|null
    {
        return (
            func_num_args() === 1
                ? $this->servicer->getService($name)
                : $this->servicer->addService($name, $service)
        );
    }

    /**
     * Get config option(s).
     *
     * Note: No set not allowed, so `$config` property is read-only and modifications are available
     * with `app/config/config.php` file only.
     *
     * @param  string|array $key
     * @param  mixed|null   $default
     * @return mixed
     */
    public function config(string|array $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    /**
     * Get registry.
     *
     * @return froq\common\object\Registry
     * @since  5.0
     */
    public static function registry(): Registry
    {
        return self::$registry;
    }

    /**
     * Run the app: applying configs, initializing session, database, cache object (when options provided in
     * config), resolve route and check validity, call "before/after" events, start output buffer and end it
     * passing that called action return to ended buffer.
     *
     * @param  string $root
     * @param  string $env
     * @param  array  $configs
     * @return void
     * @throws froq\AppException
     */
    public function run(string $root, string $env, array $configs = []): void
    {
        static $done;

        // Check/tick for run-once state.
        $done ? throw new AppException('App was already run')
              : ($done = true);

        if ($configs) {
            // Set router options first (for proper error() process).
            if ($router = array_get($configs, 'router')) {
                $this->router->setOptions($router);
            }

            // Apply dotenv configs (dropping config entrty).
            if ($dotenv = array_get($configs, 'dotenv', drop: true)) {
                $this->applyDotEnvConfigs(
                    Config::parseDotEnv($dotenv['file']),
                    !!($dotenv['global'] ?? false), // @default=false
                );
            }

            // Apply app configs.
            $this->applyConfigs($configs);
        }

        if (!$root || !$env) {
            throw new AppException('Options "root" or "env" cannot be empty');
        }

        $this->root = $root;
        $this->env  = $env;

        // Add headers & cookies (if provided).
        [$headers, $cookies] = $this->config->get(['headers', 'cookies']);
        if ($headers) foreach ($headers as $name => $value) {
            $this->response->addHeader($name, $value);
        }
        if ($cookies) foreach ($cookies as $name => $cookie) {
            @ [$value, $options] = $cookie;
            $this->response->addCookie($name, $value, $options);
        }

        // Load request stuff (globals, headers, body etc.).
        $this->request->load();

        // Generate URI segments (by root).
        $this->request->uri->generateSegments($this->root);

        // Note: These options can be emptied by developer to disable all with "null" if app won't
        // be using session/database/cache. Also, "drop" removes sensitive config data after using.
        [$session, $database, $cache] = $this->config->get(['session', 'database', 'cache'], drop: true);

        if ($session) {
            Assert::type($session, 'array|bool', new AppException(
                'Config option `session` must be array|bool, %t given', $session
            ));
            $this->session = Session::initOnce((array) $session);
        } else {
            $this->session = null;
        }

        if ($database) {
            Assert::type($database, 'array', new AppException(
                'Config option `database` must be array, %t given', $database
            ));
            $this->database = Database::initOnce($database);
        } else {
            $this->database = null;
        }

        // Note: Cache is a static instance as default.
        if ($cache) {
            Assert::type($cache, 'array', new AppException(
                'Config option `cache` must be array, %t given', $cache
            ));
            $this->cache = CacheFactory::init($cache['id'], $cache['options']);
        } else {
            $this->cache = null;
        }

        // @override
        self::$registry::set('@app', $this, true);

        // Resolve route.
        $route = $this->router->resolve(
            $uri = $this->request->getPath(),
            method: null // To check below it is allowed or not.
        );

        $method = $this->request->getMethod();

        // Found but no method allowed?
        if ($route && !isset($route[$method]) && !isset($route['*'])) {
            throw new AppException(
                'No method %s allowed for %q',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_ALLOWED, cause: new NotAllowedException()
            );
        }

        @ [$controller, $action, $actionParams] = $route[$method] ?? $route['*'] ?? null;

        // Not found?
        if (!$controller) {
            throw new AppException(
                'No controller route found for \'%s %s\'',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_FOUND, cause: new NotFoundException(),
            );
        } elseif (!$action) {
            throw new AppException(
                'No action route found for \'%s %s\'',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        if (!class_exists($controller)) {
            throw new AppException(
                'No controller class found such %q', $controller,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        } elseif (!method_exists($controller, $action) && !is_callable($action)) {
            throw new AppException(
                'No controller action found such \'%s::%s()\'', [$controller, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        $this->startOutputBuffer();

        $this->fireEvent('before');

        $controller = new $controller($this);

        try {
            if (is_string($action)) {
                $return = $controller->call($action, $actionParams);
            } elseif (is_callable($action)) {
                $return = $controller->callCallable($action, $actionParams);
            }
        } catch (Throwable $e) {
            $return = $this->error($e);
        }

        $this->fireEvent('after');

        $this->endOutputBuffer($return);
    }

    /**
     * Fallback for run failures.
     *
     * @param  Throwable $error
     * @return void
     * @since  6.0
     * @internal
     */
    public function rerun(Throwable $error): void
    {
        $this->startOutputBuffer();

        $return = $this->error($error);

        $this->endOutputBuffer($return);
    }

    /**
     * Log given message or error.
     *
     * @param  string|Stringable $message
     * @return void
     * @since  6.0
     */
    public function log(string|Stringable $message): void
    {
        if ($message instanceof Throwable) {
            $this->errorLog($message);
        } else {
            $this->logger->log($message);
        }
    }

    /**
     * Process an error routine creating default controller and calling its default error method
     * if exists and also log all errors.
     */
    private function error(Throwable $error): mixed
    {
        $this->errorLog($error);

        $this->fireEvent('error', $error);

        // Check HTTP exception related codes.
        $cause = $error->cause ?? null;
        if ($cause instanceof HttpException) {
            $code = $cause->code;
        } elseif ($error instanceof HttpException) {
            $code = ($error->code >= 400 && $error->code <= 599) ? $error->code : null;
        }

        // Also may be changed later in @default.error() method.
        $this->response->setStatus($code ?? Status::INTERNAL_SERVER_ERROR);

        $return  = null;
        $display = ini('display_errors', bool: true);

        // Try, for call @default.error() method or make an error string as return.
        try {
            $controller = $this->router->getOption('defaultController');

            // Check default controller & controller (error) method.
            if (!class_exists($controller)) {
                throw new AppError('No default controller exists such %q',
                    $controller);
            }
            if (!method_exists($controller, 'error')) {
                throw new AppError('No default controller method exists such \'%s::%s()\'',
                    [$controller, 'error']);
            }

            // Try, for controller related errors.
            try {
                $return = (new $controller($this))->error($error);
            } catch (Throwable $e) {
                $this->errorLog($e);
            }
        } catch (Throwable $e) {
            $this->errorLog($e);

            // Make an error string as return.
            $display && $return = $e . "\n";
        }

        if ($return === null || is_string($return)) {
            $return .= $this->getOutputBuffer();

            // Prepend error top of the output (if ini.display_errors is on).
            $display && $return = $error . "\n\n" . $return;
        }

        return ($return !== '') ? $return : null;
    }

    /**
     * Log an error setting logger level to ERROR.
     */
    private function errorLog(Throwable $error): bool
    {
        $level  = $this->logger->getLevel();
        $logged = $this->logger->setLevel(LogLevel::ERROR)->logError($error);

        // Restore.
        if ($level != LogLevel::ERROR) {
            $this->logger->setLevel($level);
        }

        return $logged;
    }

    /**
     * Start output buffer.
     */
    private function startOutputBuffer(): void
    {
        ob_start();
        ob_implicit_flush(false);
        ini_set('implicit_flush', false);
    }

    /**
     * End output buffer, sending/ending response.
     */
    private function endOutputBuffer(mixed $return): void
    {
        // Handle output/return responses.
        if ($this->response->allowsBody()) {
            $content = $this->response->body->getContent();

            // Actions that use echo/print/view()/response.setBody() will return null.
            if ($content === null && ($return === null || is_string($return))) {
                $return .= $this->getOutputBuffer();
            }

            // Content of body or returned content from action.
            $content ??= $return;

            // Call user output handler if provided, so output must return back.
            if ($this->eventManager->has('output')) {
                $content = $this->eventManager->fire('output', $content);
            }

            $this->response->setBody($content, $this->response->body->getAttributes());
        }
        // Handle non-body responses.
        else {
            $this->response->setBody(null, null);
        }

        // The end..
        $this->response->end();
    }

    /**
     * Get output buffer.
     */
    private function getOutputBuffer(): string
    {
        // Actions that use echo/print/view()/response.setBody() will return null.
        // So, output buffer must be collected as body content if body content is null.
        $buffer = '';

        // Handle echo/print stuff.
        while (ob_get_level()) {
            $buffer .= ob_get_clean();
        }

        return $buffer;
    }

    /**
     * Fire an event (if provided in index.php via on/off methods).
     */
    private function fireEvent(string $name, mixed ...$arguments): void
    {
        if ($this->eventManager->has($name)) {
            $this->eventManager->fire($name, ...$arguments);
        }
    }

    /**
     * Apply configs.
     */
    private function applyConfigs(array $configs): void
    {
        // Update current options.
        $this->config->update($configs);

        // Set timezone, encoding, locale options.
        $this->config->extract(['timezone', 'encoding', 'locales', 'ini'],
            $timezone, $encoding, $locales, $ini);

        if ($timezone) {
            date_default_timezone_set($timezone);
        }

        if ($encoding) {
            ini_set('default_charset', $encoding);
            ini_set('internal_encoding', $encoding);
        }

        if ($locales) {
            // Must be like [LC_TIME => 'en_US'].
            foreach ($locales as $category => $locale) {
                setlocale($category, $locale);
            }
        }

        if ($ini) {
            // Must be like [string => scalar].
            foreach ($ini as $option => $value) {
                ini_set($option, $value);
            }
        }

        // Set/reset options.
        $this->config->extract(['logger', 'routes', 'services'],
            $logger, $routes, $services);

        if ($logger) {
            foreach ($logger as $option => $value) {
                $this->logger->setOption($option, $value);
            }
        }

        $routes   && $this->router->addRoutes($routes);
        $services && $this->servicer->addServices($services);
    }

    /**
     * Apply dot-env configs.
     */
    private function applyDotEnvConfigs(array $configs, bool $global): void
    {
        foreach ($configs as $name => $value) {
            putenv($name . '=' . $value);

            // When it was set as true.
            $global && $_ENV[$name] = $value;
        }
    }
}
