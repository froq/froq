<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use froq\{AppException, Handler, Router, Servicer, mvc\Controller};
use froq\{logger\Logger, event\Events, session\Session, database\Database};
use froq\common\{trait\InstanceTrait, object\Config, object\Factory, object\Registry};
use froq\http\{Request, Response, response\Status,
    exception\server\InternalServerErrorException,
    exception\client\NotFoundException, exception\client\NotAllowedException};
use froq\cache\{Cache, CacheFactory};
use Throwable;

/**
 * App.
 *
 * Represents an application entity which is responsible with all application logics;
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
    /** @see froq\common\trait\InstanceTrait */
    use InstanceTrait;

    /**
     * Root (provides options like "app.local/v1/book/1" for versioning etc.).
     * @var string
     */
    private string $root = '/';

    /** @var string */
    private string $dir;

    /** @var string */
    private string $env;

    /** @var froq\common\object\Config */
    private Config $config;

    /** @var froq\logger\Logger */
    private Logger $logger;

    /** @var froq\events\Events */
    private Events $events;

    /** @var froq\http\Request */
    private Request $request;

    /** @var froq\http\Response */
    private Response $response;

    /** @var froq\session\Session|null @since 3.18 */
    private Session $session;

    /** @var froq\database\Database|null @since 4.0 */
    private Database $database;

    /** @var froq\cache\Cache|null @since 4.10 */
    private Cache $cache;

    /** @var froq\Router @since 4.0 */
    private Router $router;

    /** @var froq\Servicer @since 4.0 */
    private Servicer $servicer;

    /** @var froq\common\object\Register @since 5.0 */
    private static Registry $registry;

    /**
     * Constructor.
     *
     * @throws froq\AppException
     */
    private function __construct()
    {
        // App dir is required (@see pub/index.php).
        defined('APP_DIR') || throw new AppException('No APP_DIR defined');

        $this->logger   = new Logger(['level' => Logger::ALL]);
        $this->request  = new Request($this);
        $this->response = new Response($this);

        [$this->dir, $this->config, $this->events, $this->router, $this->servicer, self::$registry] = [
            APP_DIR, new Config(), new Events(), new Router(), new Servicer(), new Registry()
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
        return ($this->root == $this->request->getContext());
    }

    /**
     * Get root.
     *
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Get dir.
     *
     * @return string|null
     */
    public function dir(): string|null
    {
        return $this->dir ?? null;
    }

    /**
     * Get env.
     *
     * @return string|null
     */
    public function env(): string|null
    {
        return $this->env ?? null;
    }

    /**
     * Check local env.
     *
     * @return bool|null
     * @since  5.0
     */
    public function local(): bool|null
    {
        return defined('__local__') ? __local__ : null;
    }

    /**
     * Get runtime.
     *
     * @param  int  $precision
     * @param  bool $format
     * @return float|string
     * @since  4.0 Replaced with loadTime().
     */
    public function runtime(int $precision = 3, bool $format = false): float|string
    {
        $runtime = round(microtime(true) - APP_START_TIME, $precision);

        return !$format ? $runtime : sprintf('%.*F', $precision, $runtime);
    }

    /**
     * Get a config option(s) or config object.
     *
     * @param  string|array|null $key
     * @param  any|null          $default
     * @return any|null|froq\common\object\Config
     */
    public function config(string|array $key = null, $default = null)
    {
        if (!func_num_args()) {
            return $this->config;
        }

        // Set not allowed, so config readonly and set available with config.php only.
        return $this->config->get($key, $default);
    }

    /**
     * Get logger.
     *
     * @return froq\logger\Logger
     */
    public function logger(): Logger
    {
        return $this->logger;
    }

    /**
     * Get events.
     *
     * @return froq\events\Events
     */
    public function events(): Events
    {
        return $this->events;
    }

    /**
     * Get request.
     *
     * @return froq\http\Request|null
     */
    public function request(): Request|null
    {
        return $this->request ?? null;
    }

    /**
     * Get response.
     *
     * @return froq\http\Response|null
     */
    public function response(): Response|null
    {
        return $this->response ?? null;
    }

    /**
     * Get session.
     *
     * @return froq\session\Session|null
     * @since  3.18
     */
    public function session(): Session|null
    {
        return $this->session ?? null;
    }

    /**
     * Get database.
     *
     * @return froq\database\Database|null
     * @since  4.0
     */
    public function database(): Database|null
    {
        return $this->database ?? null;
    }

    /**
     * Put/get a cache item or get cache object, throw `AppException` if no cache object initiated.
     *
     * @param  string|int|array<string|int>|null $key
     * @param  any|null                          $value
     * @param  int|null                          $ttl
     * @return any|null|froq\cache\agent\Cache
     * @throws froq\AppException
     * @since  4.10
     */
    public function cache(string|int|array $key = null, $value = null, int $ttl = null)
    {
        if (isset($this->cache)) {
            return match (func_num_args()) {
                      0 => $this->cache, // None given.
                      1 => $this->cache->read($key),
                default => $this->cache->write($key, $value, $ttl)
            };
        }

        throw new AppException('No cache agent initiated yet, be sure `cache` field is not'
            . ' empty in config');
    }

    /**
     * Drop a cache item from cache, throw `AppException` if no cache object initiated.
     *
     * @param  string|int|array<string|int> $key
     * @return bool
     * @throws froq\AppException
     * @since  4.10
     */
    public function uncache(string|int|array $key): bool
    {
        if (isset($this->cache)) {
            return $this->cache->remove($key);
        }

        throw new AppException('No cache agent initiated yet, be sure `cache` field is not'
            . ' empty in config');
    }

    /**
     * Get router.
     *
     * @return froq\Router
     * @since  4.0
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get servicer.
     *
     * @return froq\Servicer
     * @since  4.0
     */
    public function servicer(): Servicer
    {
        return $this->servicer;
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
     * Set/get a service.
     *
     * @param  string                $name
     * @param  object|callable|array $service
     * @return object|callable|null
     * @since  4.0
     */
    public function service(string $name, object|callable|array $service = null): object|callable|null
    {
        return (func_num_args() == 1)
             ? $this->servicer->getService($name)
             : $this->servicer->addService($name, $service);
    }

    /**
     * Run the app: applying configs, initializing session, database, cache object (when options provided in
     * config), resolve route and check validity, call "before/after" events, start output buffer and end it
     * passing that called action return to ended buffer.
     *
     * @param  array<string, any> $options
     * @return void
     * @throws froq\AppException
     */
    public function run(array $options): void
    {
        static $done;

        // Check/tick for run-once state.
        $done ? throw new AppException('App was already run')
              : ($done = true);

        // Apply run options (user options) (@see pub/index.php).
        @ ['configs' => $configs, 'env' => $env, 'root' => $root] = $options;

        if ($configs != null) {
            // Set router options first (for proper error() process).
            if (pick($configs, 'router', $router)) {
                $this->router->setOptions($router);
            }

            // Apply dotenv configs.
            if (pluck($configs, 'dotenv', $dotenv)) {
                $this->applyDotenvConfigs(
                    Config::parseDotenv($dotenv['file']),
                    !!($dotenv['global'] ?? false), // @default
                );
            }

            // Apply app configs.
            $this->applyConfigs($configs);
        }

        // Check/set env & root stuff.
        if ($env == '' || $root == '') {
            throw new AppException('Options `env` or `root` must not be empty');
        }
        $this->env  = $env;
        $this->root = $root;

        // Add headers & cookies (if provided).
        [$headers, $cookies] = $this->config(['headers', 'cookies']);
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
        $this->request->uri()->generateSegments($this->root);

        // These options can be emptied by developer to disable all with "null" if app won't
        // be using session/database/cache. Also, "pull" removes sensitive config data after using.
        [$session, $database, $cache] = $this->config->pull(['session', 'database', 'cache']);

        isset($session)  && $this->session  = Factory::initOnce(Session::class, $session);
        isset($database) && $this->database = Factory::initOnce(Database::class, $database);

        // Note: cache is a "static" instance as default.
        if ($cache != null) {
            $this->cache = CacheFactory::init($cache['id'], $cache['agent']);
        }

        // @override
        self::$registry::set('@app', $this, true);

        // Resolve route.
        $route = $this->router->resolve(
            $uri = $this->request->getContext(),
            method: null // To check below it is allowed or not.
        );

        $method = $this->request->getMethod();

        // Found but no method allowed?
        if ($route != null && !isset($route[$method]) && !isset($route['*'])) {
            throw new AppException(
                'No method %s allowed for `%s`',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_ALLOWED, cause: new NotAllowedException()
            );
        }

        @ [$controller, $action, $actionParams] = $route[$method] ?? $route['*'] ?? null;

        // Not found?
        if ($controller == null) {
            throw new AppException(
                'No controller route found for `%s %s`',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_FOUND, cause: new NotFoundException(),
            );
        } elseif ($action == null) {
            throw new AppException(
                'No action route found for `%s %s`',
                [$method, htmlspecialchars(rawurldecode($uri))],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        } elseif (!class_exists($controller)) {
            throw new AppException(
                'No controller class found such `%s`', $controller,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        } elseif (!method_exists($controller, $action) && !is_callable($action)) {
            throw new AppException(
                'No controller action found such `%s::%s()`', [$controller, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        $this->startOutputBuffer();

        // Call before event if exists.
        $this->events->fire('app.before');

        $controller = new $controller($this);

        if (is_string($action)) {
            $return = $controller->call($action, $actionParams);
        } elseif (is_callable($action)) {
            $return = $controller->callCallable($action, $actionParams);
        }

        // Call after event if exists.
        $this->events->fire('app.after');

        $this->endOutputBuffer($return);
    }

    /**
     * Process an error routine creating default controller and calling its default error method and
     * ending output buffer, also log error. Throw `AppException` if no default controller or error
     * method not exists.
     *
     * @param  Throwable $error
     * @param  bool      $log
     * @return void
     * @since  4.0
     */
    public function error(Throwable $error, bool $log = true): void
    {
        $log && $this->errorLog($error);

        // Call user error handler if provided.
        $this->events->fire('app.error', $error);

        // Status may be changed later in @default.error().
        $this->response->setStatusCode(Status::INTERNAL_SERVER_ERROR);

        // Clear outputs (@default.error() will work below for output).
        // while (ob_get_level()) {
        //     ob_end_clean();
        // }

        $class  = new \Classe($this->router->getOption('defaultController'));
        $method = Controller::ERROR_ACTION;

        if (!$class->exists()) {
            throw new AppException(
                'No default controller exists such `%s`', $class,
                code: Status::INTERNAL_SERVER_ERROR, cause: new InternalServerErrorException()
            );
        } elseif (!$class->existsMethod($method)) {
            throw new AppException(
                'No default controller method exists such `%s::%s()`', [$class, $method],
                code: Status::INTERNAL_SERVER_ERROR, cause: new InternalServerErrorException()
            );
        }

        // Call default controller error method.
        $return = $class->init($this)->{$method}($error);

        // Prepend error top of the output (if ini.display_errors is on).
        if ($return == null || is_string($return)) {
            $return = (string) $return;
            $display = ini_get('display_errors');
            if ($display || $display === 'on') {
                $return = trim($error . "\n\n" . $return);
            }
        }

        $this->endOutputBuffer($return, true);
    }

    /**
     * Log an error setting logger level to ERROR.
     *
     * @param  string|Throwable $error
     * @param  bool             $separate
     * @return bool
     * @since  4.0
     */
    public function errorLog(string|Throwable $error, bool $separate = true): bool
    {
        $level  = $this->logger->getLevel();
        $logged = $this->logger->setLevel(Logger::ERROR)->logError($error, $separate);

        $this->logger->setLevel($level); // Restore.

        return $logged;
    }

    /**
     * Start output buffer.
     *
     * @return void
     */
    private function startOutputBuffer(): void
    {
        ob_start();
        ob_implicit_flush(false);
        ini_set('implicit_flush', false);
    }

    /**
     * End output buffer, sending/ending response.
     *
     * @param  mixed $return
     * @param  bool  $error @internal
     * @return void
     */
    private function endOutputBuffer(mixed $return, bool $error = false): void
    {
        $response = $this->response();
        $response || throw new AppException('App has no response yet');

        $body = $response->getBody();
        $attributes = $body->getAttributes();

        // Handle redirections.
        if ($response->status()->isRedirect()) {
            while (ob_get_level()) {
                ob_end_clean();
            }

            $response->setBody(null, ['type' => 'n/a'] + $attributes);
        }
        // Handle outputs & returns.
        else {
            $content = null;

            if ($error) {
                // Pass, return comes from App.error() already.
            } else {
                $content = $body->getContent();

                // Actions that use echo/print/view()/response.setBody() will return null.
                // So, output buffer must be collected as body content if body content is null.
                if ($content === null && ($return == null || is_string($return))) {
                    $return = (string) $return;
                    while (ob_get_level()) {
                        $return .= ob_get_clean();
                    }
                }
            }

            // Content of body or returned content from action.
            $content ??= $return;

            // Call user output handler if provided.
            if ($this->events->has('app.output')) {
                $content = $this->events->fire('app.output', $content);
            }

            $response->setBody($content, $attributes);
        }

        // The end..
        $response->end();
    }

    /**
     * Apply configs.
     *
     * @param  array $configs
     * @return void
     */
    private function applyConfigs(array $configs): void
    {
        // Update current options.
        $this->config->update($configs);

        // Set timezone, encoding, locale options.
        $this->config->extract(['timezone', 'encoding', 'locales', 'ini'],
            $timezone, $encoding, $locales, $ini);

        if ($timezone != null) {
            date_default_timezone_set($timezone);
        }

        if ($encoding != null) {
            ini_set('default_charset', $encoding);
            ini_set('internal_encoding', $encoding);
        }

        if ($locales != null) {
            // Must be like eg: [LC_TIME => 'en_US' or 'en_US.utf-8'].
            foreach ($locales as $category => $locale) {
                setlocale($category, $locale);
            }
        }

        if ($ini != null) {
            // Must be like eg: [string => scalar].
            foreach ($ini as $option => $value) {
                ini_set($option, $value);
            }
        }

        // Set/reset options.
        $this->config->extract(['logger', 'routes', 'services'],
            $logger, $routes, $services);

        $logger   && $this->logger->setOptions($logger);
        $routes   && $this->router->addRoutes($routes);
        $services && $this->servicer->addServices($services);
    }

    /**
     * Apply dot-env configs.
     *
     * @param  array $configs
     * @param  bool  $global
     * @return void
     * @since  4.14
     */
    private function applyDotenvConfigs(array $configs, bool $global): void
    {
        foreach ($configs as $name => $value) {
            putenv($name . '=' . $value);

            // When was set as global.
            $global && ($_ENV[$name] = $value);
        }
    }
}
