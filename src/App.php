<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq;

use froq\common\traits\SingletonTrait;
use froq\common\objects\{Factory, Registry};
use froq\http\{Request, Response, response\Status};
use froq\{config\Config, logger\Logger, event\Events};
use froq\{session\Session, database\Database, cache\Cache, cache\CacheFactory};
use froq\{AppException, Handler, Router, Servicer, mvc\Controller};
use Throwable;

/**
 * App.
 *
 * @package froq
 * @object  froq\App
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class App
{
    /**
     * Singleton trait.
     * @see froq\common\traits\SingletonTrait
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
     * @var string
     */
    private string $env;

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
     * @var froq\http\Request
     */
    private Request $request;

    /**
     * Response.
     * @var froq\http\Response
     */
    private Response $response;

    /**
     * Session.
     * @var froq\session\Session|null
     * @since 3.18
     */
    private Session $session;

    /**
     * Database.
     * @var froq\database\Database|null
     * @since 4.0
     */
    private Database $database;

    /**
     * Cache.
     * @var froq\cache\Cache|null
     * @since 4.10
     */
    private Cache $cache;

    /**
     * Router.
     * @var froq\Router
     * @since 4.0
     */
    private Router $router;

    /**
     * Servicer.
     * @var froq\Servicer
     * @since 4.0
     */
    private Servicer $servicer;

    /**
     * Constructor.
     * @throws froq\AppException
     */
    private function __construct()
    {
        // App dir is required (@see pub/index.php).
        if (!defined('APP_DIR')) {
            throw new AppException('APP_DIR is not defined');
        }

        $this->request = new Request($this);
        $this->response = new Response($this);

        [$this->dir, $this->config, $this->logger, $this->events, $this->router, $this->servicer]
            = [APP_DIR, new Config(), new Logger(), new Events(), new Router(), new Servicer()];

        // Register app.
        Registry::set('@app', $this, false);

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
     * Is root.
     * @return bool
     */
    public function isRoot(): bool
    {
        return ($this->root == $this->request->uri()->get('path'));
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
     * @return string
     */
    public function env(): string
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
     * @throws froq\AppException If ket type not valid.
     */
    public function config($key = null, $valueDefault = null)
    {
        // Set is not allowed, so config readonly and set available in cfg.php files only.
        if ($key === null) {
            return $this->config;
        }
        if (is_string($key) || is_array($key)) {
            return $this->config->get($key, $valueDefault);
        }

        throw new AppException('Only string, array and null keys allowed for "%s()" method, '.
            '"%s" given', [__method__, gettype($key)]);
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
        return $this->request ?? null;
    }

    /**
     * Response.
     * @return ?froq\http\Response
     */
    public function response(): ?Response
    {
        return $this->response ?? null;
    }

    /**
     * Session.
     * @return ?froq\session\Session
     * @since  3.18
     */
    public function session(): ?Session
    {
        return $this->session ?? null;
    }

    /**
     * Database.
     * @return ?froq\database\Database
     * @since  4.0
     */
    public function database(): ?Database
    {
        return $this->database ?? null;
    }

    /**
     * Cache.
     * @param  string|int|array<string|int>|null $key
     * @param  any                               $value
     * @param  int|null                          $ttl
     * @return any|null|froq\cache\agent\Cache
     * @throws froq\AppException
     * @since  4.10
     */
    public function cache($key = null, $value = null, int $ttl = null)
    {
        if (!isset($this->cache)) {
            throw new AppException('No cache agent initiated yet, be sure "cache" field is '.
                'not empty in config');
        }

        switch (func_num_args()) {
             case 0: return $this->cache; // None given.
             case 1: return $this->cache->read($key);
            default: return $this->cache->write($key, $value, $ttl);
        }
    }

    /**
     * Uncache.
     * @param  string|int|array<string|int> $key
     * @return bool
     * @throws froq\AppException
     * @since  4.10
     */
    public function uncache($key): bool
    {
        if (!isset($this->cache)) {
            throw new AppException('No cache agent initiated yet, be sure "cache" field is '.
                'not empty in config');
        }

        return $this->cache->remove($key);
    }

    /**
     * Router.
     * @return froq\Router
     * @since  4.0
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Servicer.
     * @return froq\Servicer
     * @since  4.0
     */
    public function servicer(): Servicer
    {
        return $this->servicer;
    }

    /**
     * Defines a route with given method(s).
     *
     * @param  string          $route
     * @param  string          $methods
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function route(string $route, string $methods, $call): self
    {
        $this->router->addRoute($route, $methods, $call);

        return $this;
    }

    /**
     * Defines a route with GET method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function get(string $route, $call): self
    {
        return $this->route($route, 'GET', $call);
    }

    /**
     * Defines a route with POST method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function post(string $route, $call): self
    {
        return $this->route($route, 'POST', $call);
    }

    /**
     * Defines a route with PUT method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function put(string $route, $call): self
    {
        return $this->route($route, 'PUT', $call);
    }

    /**
     * Defines a route with DELETE method.
     *
     * @param  string          $route
     * @param  string|callable $call
     * @return self
     * @since  4.0
     */
    public function delete(string $route, $call): self
    {
        return $this->route($route, 'DELETE', $call);
    }

    /**
     * Gets or sets a service.
     *
     * @param  string         $name
     * @param  array|callable $service
     * @return ?object|void
     * @since  4.0
     */
    public function service(string $name, $service = null): ?object
    {
        return !$service ? $this->servicer->getService($name)
                         : $this->servicer->addService($name, $service);
    }

    /**
     * Run.
     * @param  array<string, any> $options
     * @return void
     * @throws froq\AppException
     */
    public function run(array $options = null): void
    {
        // Apply run options (user options) (@see skeleton/pub/index.php).
        @ ['env' => $env, 'root' => $root, 'configs' => $configs] = $options;

        // Set router options first (for proper error() process).
        isset($configs['router']) && $this->router->setOptions($configs['router']);

        if ($env == '' || $root == '') {
            throw new AppException('Options "env" or "root" must not be empty');
        }

        $this->env = $env;
        $this->root = $root;

        if ($configs != null) {
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

        // Add headers & cookies if provided.
        [$headers, $cookies] = $this->config(['headers', 'cookies']);
        if ($headers) foreach ($headers as $name => $value) {
            $this->response->addHeader($name, $value);
        }
        if ($cookies) foreach ($cookies as $name => $cookie) {
            @ [$value, $options] = $cookie;
            $this->response->addCookie($name, $value, $options);
        }

        // Generate URI segments by the root.
        $this->request->uri()->generateSegments($this->root);

        // These options can be emptied by developer to disable all with "null" if app won't
        // be using session/database/cache. Also remove sensitive config data after using.
        [$session, $database, $cache] = $this->config->pull(['session', 'database', 'cache']);
        if (isset($session)) {
            $this->session = Factory::initSingle(Session::class, $session);
        }
        if (isset($database)) {
            $this->database = Factory::initSingle(Database::class, $database);
        }

        // Cache is a "static" instance as default.
        if (isset($cache)) {
            $this->cache = CacheFactory::init($cache['id'], $cache['agent']);
        }

        // @override
        Registry::set('@app', $this, true);

        // Resolve route.
        $route = $this->router->resolve(
            $uri     = $this->request->uri()->get('path'),
            $method  = null, // To check below it is allowed or not.
            $options = $this->config->get('router')
        );

        $method = $this->request->method()->getName();

        // Found but no method allowed?
        if ($route != null && !isset($route[$method]) && !isset($route['*'])) {
            throw new AppException('No method "%s" allowed for URI: "%s"',
                [$method, htmlspecialchars(rawurldecode($uri))], Status::METHOD_NOT_ALLOWED);
        }

        @ [$controller, $action, $actionParams] = $route[$method] ?? $route['*'] ?? null;

        // Not found?
        if ($controller == null) {
            throw new AppException('No controller route found for URI: "%s %s"',
                [$method, htmlspecialchars(rawurldecode($uri))], Status::NOT_FOUND);
        } elseif ($action == null) {
            throw new AppException('No action route found for URI: "%s %s"',
                [$method, htmlspecialchars(rawurldecode($uri))], Status::NOT_FOUND);
        } elseif (!class_exists($controller)) {
            throw new AppException('No controller class found such "%s"',
                [$controller], Status::NOT_FOUND);
        } elseif (!is_callable($action) && !is_callable([$controller, $action])) {
            throw new AppException('No controller action found such "%s::%s()"',
                [$controller, $action], Status::NOT_FOUND);
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
     * Error.
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

        [$controller, $method] = [$this->router->getOptions()['defaultController'], Controller::ERROR_ACTION];

        if (!class_exists($controller)) {
            throw new AppException('No default controller exists such "%s"',
                [$controller]);
        }
        if (!method_exists($controller, $method)) {
            throw new AppException('No default controller method exists such "%s::%s"',
                [$controller, $method]);
        }

        // Call default error method of default controller.
        $return = (new $controller($this))->{$method}($error);

        // Prepend error top of the output (if ini.display_errors is on).
        if ($return == null || is_string($return)) {
            $displayErrors = ini('display_errors', '', true);
            if ($displayErrors) {
                $return = trim($error ."\n\n". $return);
            }
        }

        $this->endOutputBuffer($return, true);
    }

    /**
     * Error log.
     * @param  string|Throwable $error
     * @return void
     * @since  4.0
     */
    public function errorLog($error): void
    {
        $this->logger->logError($error);
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
     * @param  any       $return
     * @param  bool|null $isError @internal (@see Message.setBody()) @cancel
     * @return void
     */
    private function endOutputBuffer($return, bool $isError = null): void
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

            // Pass, return comes from App.error() already.
            if ($isError) {}
            // Actions that use echo/print/view()/response.setBody() will return null.
            elseif ($return == null || is_string($return)) {
                while (ob_get_level()) {
                    $return .= ob_get_clean();
                }
            }

            // Returned content from action or set on body.
            $content = $content ?? $return;

            // Call user output handler if provided.
            if ($this->events->has('app.output')) {
                $content = $this->events->fire('app.output', $content);
            }

            $response->setBody($content, $contentAttributes, $isError);
        }

        $exposeAppRuntime = $this->config('exposeAppRuntime');
        if ($exposeAppRuntime && ($exposeAppRuntime === true || $exposeAppRuntime === $this->env)) {
            $response->setHeader('X-App-Runtime', sprintf('%.4f', $this->runtime()));
        }

        // The end..
        $response->end();
    }

    /**
     * Apply configs.
     * @param  array $configs
     * @return void
     */
    private function applyConfigs(array $configs): void
    {
        $this->config->update($configs);

        // Set timezone, encoding, locale options.
        [$timezone, $encoding, $locales]
            = $this->config->get(['timezone', 'encoding', 'locales']);

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

        // Set/reset options.
        [$logger, $routes, $services]
            = $this->config->get(['logger', 'routes', 'services']);

        $logger   && $this->logger->setOptions($logger);
        $routes   && $this->router->addRoutes($routes);
        $services && $this->servicer->addServices($services);
    }

    /**
     * Apply dotenv configs.
     * @param  array $configs
     * @param  bool  $global
     * @return void
     * @since  4.14
     */
    private function applyDotenvConfigs(array $configs, bool $global): void
    {
        foreach ($configs as $name => $value) {
            putenv($name .'='. $value);
            if ($global) {
                $_ENV[$name] = $value;
            }
        }
    }
}
