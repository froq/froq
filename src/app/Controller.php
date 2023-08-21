<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app;

use froq\http\{Request, Response, HttpException, request\Segments, response\Status,
    response\payload\Payload, response\payload\JsonPayload, response\payload\XmlPayload,
    response\payload\HtmlPayload, response\payload\FilePayload, response\payload\ImagePayload,
    response\payload\PlainPayload, exception\client\NotFoundException};
use froq\{App, Router, session\Session, database\Database, util\Objects};
use ReflectionMethod, ReflectionFunction, ReflectionNamedType, ReflectionException;

/**
 * Base class of `app\controller` classes.
 *
 * @package froq\app
 * @class   froq\app\Controller
 * @author  Kerem Güneş
 * @since   4.0, 6.0
 */
class Controller
{
    /** Namespace of controllers. */
    public final const NAMESPACE      = 'app\controller';

    /** Default controller & action. */
    public final const DEFAULT        = 'app\controller\IndexController',
                       DEFAULT_SHORT  = 'IndexController',
                       ACTION_DEFAULT = 'index';

    /** Suffix names. */
    public final const SUFFIX         = 'Controller',
                       ACTION_SUFFIX  = 'Action';

    /** Action names. */
    public final const INDEX_ACTION   = 'index',
                       ERROR_ACTION   = 'error';

    /** Default names. */
    public final const NAME_DEFAULT   = '@default',
                       NAME_CLOSURE   = '@closure';

    /** App instance. */
    public readonly App $app;

    /** Request instance. */
    public readonly Request $request;

    /** Response instance. */
    public readonly Response $response;

    /** Repository instance. */
    public readonly Repository $repository;

    /** Session instance. */
    public readonly Session $session;

    /** View instance. */
    public readonly View $view;

    /** Use repository option. */
    public bool $useRepository = false;

    /** Use session option. */
    public bool $useSession = false;

    /** Use view option. */
    public bool $useView = false;

    /** Action of this controller. */
    private string $action;

    /** Action params of this controller. */
    private array $actionParams;

    /** Before/after method existence states. */
    private bool $before = false, $after = false;

    /**
     * Constructor.
     *
     * @param  froq\App|null $app
     * @throws froq\app\ControllerException
     */
    public final function __construct(App $app = null)
    {
        // Try active app object if none given.
        $this->app = $app ?? (
            function_exists('app') ? app()
                : throw new ControllerException('No app object to deal')
        );

        // Copy as a shortcut for subclasses.
        $this->request  = $this->app->request;
        $this->response = $this->app->response;

        // Load usings.
        $this->useRepository && $this->loadRepository();
        $this->useSession    && $this->loadSession();
        $this->useView       && $this->loadView();

        // Store this controller (as last controller).
        $this->app::registry()::set('@controller', $this, false);

        // Set before/after ticks these called in call()/callCallable() methods.
        $this->before = method_exists($this, 'before');
        $this->after  = method_exists($this, 'after');

        // Call init() method if defined in subclass.
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * Destructor.
     */
    public final function __destruct()
    {
        // Call dinit() method if defined in subclass.
        if (method_exists($this, 'dinit')) {
            $this->dinit();
        }
    }

    /**
     * Get repository.
     *
     * @return froq\app\Repository|null
     */
    public final function getRepository(): Repository|null
    {
        return $this->repository ?? null;
    }

    /**
     * Get session.
     *
     * @return froq\session\Session
     */
    public final function getSession(): Session|null
    {
        return $this->session ?? null;
    }

    /**
     * Get view.
     *
     * @return froq\app\View|null
     */
    public final function getView(): View|null
    {
        return $this->view ?? null;
    }

    /**
     * Get name of controller that run at the time, creating if not set yet.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this::class;
    }

    /**
     * Get short name of controller that run at the time.
     *
     * @param  bool $suffix
     * @return string
     */
    public final function getShortName(bool $suffix = false): string
    {
        $name = Objects::getShortName($this);

        if (!$suffix && str_ends_with($name, Controller::SUFFIX)) {
            $name = substr($name, 0, -strlen(Controller::SUFFIX));
        }

        return $name;
    }

    /**
     * Get action name that called at the time.
     *
     * @return string
     */
    public final function getActionName(): string
    {
        return $this->action ?? '';
    }

    /**
     * Get action short name that called at the time.
     *
     * @param  bool $suffix
     * @return string
     */
    public final function getActionShortName(bool $suffix = false): string
    {
        $action = $this->action ?? '';

        if (!$suffix && str_ends_with($action, Controller::ACTION_SUFFIX)) {
            $action = substr($action, 0, -strlen(Controller::ACTION_SUFFIX));
        }

        return $action;
    }

    /**
     * Get current controller path built with action that called at the time.
     *
     * @param  bool $full
     * @return string
     */
    public final function getPath(bool $full = false): string
    {
        return !$full ? $this->getShortName() . '.' . $this->getActionShortName()
                      : strtr($this->getName(), '\\', '.') . '.' . $this->getActionName();
    }

    /**
     * Check an action param's existence.
     *
     * @param  string $name
     * @return bool
     */
    public final function hasActionParam(string $name): bool
    {
        return isset($this->actionParams[$name]);
    }

    /**
     * Set an action param by given name/value.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public final function setActionParam(string $name, $value): void
    {
        $this->actionParams[$name] = $value;
    }

    /**
     * Get an action param by given name.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @return mixed|null
     */
    public final function getActionParam(string $name, mixed $default = null): mixed
    {
        return $this->actionParams[$name] ?? $default;
    }

    /**
     * Check all action param's existence, or given names only.
     *
     * @param  array<string>|null $names
     * @return bool
     */
    public final function hasActionParams(array $names = null): bool
    {
        $params = $this->actionParams ?? [];

        if ($names === null) {
            return !empty($params);
        }

        return array_isset($params, ...$names);
    }

    /**
     * Set action params by given name/value order.
     *
     * @param  array<string, mixed> $params
     * @return void
     */
    public final function setActionParams(array $params): void
    {
        foreach ($params as $name => $value) {
            $this->setActionParam($name, $value);
        }
    }

    /**
     * Get all action params, or by given names only.
     *
     * @param  array<string>|null $names
     * @param  array|null         $defaults
     * @param  bool               $combine
     * @return array<mixed>
     */
    public final function getActionParams(array $names = null, array $defaults = null, bool $combine = false): array
    {
        $params = $this->actionParams ?? [];

        if ($names !== null) {
            return array_select($params, $names, $defaults, combine: $combine);
        }

        // Leave combined with keys or values only.
        $combine || $params = array_values($params);

        return $params;
    }

    /**
     * Load (initialize) the repository object for the owner controller if controller's `$useRepository`
     * property set to true and `$repository` property is not set yet.
     *
     * @return void
     */
    public final function loadRepository(): void
    {
        if (!isset($this->repository)) {
            $name = $this->getShortName();
            $base = null;

            // Check whether controller is a subcontroller.
            if (substr_count($controller = static::class, '\\') > 2) {
                $base = substr($controller, 0, strrpos($controller, '\\'));
                $base = substr($base, strrpos($base, '\\') + 1);
            }

            $class = !$base ? Repository::NAMESPACE . '\\' . $name . Repository::SUFFIX
                            : Repository::NAMESPACE . '\\' . $base . '\\' . $name . Repository::SUFFIX;

            // Try to use parent's repository class if parent uses a repository.
            if (!class_exists($class)) {
                $parent = get_parent_class($this);
                while ($parent && $parent !== self::class) {
                    // Make repository's class name fully qualified.
                    $class = str_replace(Controller::NAMESPACE, Repository::NAMESPACE, Objects::getNamespace($parent))
                        . '\\' . (substr(Objects::getShortName($parent), 0, -strlen(Controller::SUFFIX)) . Repository::SUFFIX);

                    // Validate existence & break.
                    if (class_exists($class)) {
                        break;
                    }

                    // Try moving to next parent.
                    $parent = get_parent_class($parent);
                }
            }

            $this->repository = $this->initRepository($class);
        }
    }

    /**
     * Load session object for the owner controller if controller's `$useSession` property
     * set to true, throw a `ControllerException` if app has no session.
     *
     * @return void
     * @throws froq\app\ControllerException
     */
    public final function loadSession(): void
    {
        if (!isset($this->session)) {
            $this->app->session ?? throw new ControllerException(
                'App has no session object, be sure "session" option is not empty in config'
            );

            // @cancel: Must be started on-demand in actions or init() method.
            // $this->app->session->start();

            $this->session = $this->app->session;
        }
    }

    /**
     * Load (initialize) the view object for the owner controller if controller's `$useView` property
     * set to true and `$view` property is not set yet, throw a `ControllerException` if no `view.layout`
     * option found in configuration.
     *
     * @return void
     * @throws froq\app\ControllerException
     */
    public final function loadView(): void
    {
        if (!isset($this->view)) {
            $layout = $this->app->config('view.layout')
                ?: throw new ControllerException('No "view.layout" option found in config');

            $this->view = new View($this);
            $this->view->setLayout($layout);
        }
    }

    /**
     * View a view file with given `$fileData` arguments if provided, rendering the file in a wrapped output
     * buffer.
     *
     * @param  string     $file
     * @param  array|null $fileData
     * @param  int|null   $status
     * @return string
     * @throws froq\app\ControllerException
     */
    public final function view(string $file, array $fileData = null, int $status = null): string
    {
        if (!isset($this->view)) {
            throw new ControllerException(
                'No $view property set yet, be sure $useView is true in class %q',
                static::class
            );
        }

        // Shortcut for response status.
        $status && $this->response->setStatus($status);

        return $this->view->render($file, $fileData);
    }

    /**
     * Forward an internal call to other call (controller method) with given call arguments. The `$call`
     * parameter must be fully qualified for explicit methods without `Controller` and `Action` suffixes
     * eg: `Book.show`, otherwise `index` method does not require that explicity.
     *
     * @param  string $call
     * @param  array  $callArgs
     * @return mixed
     * @throws froq\app\ControllerException
     */
    public final function forward(string $call, array $callArgs = []): mixed
    {
        [$controller, $action, $actionParams] = Router::prepare($call, $callArgs);

        if (!$controller || !$action) {
            throw new ControllerException(
                "Invalid call directive %q, use 'Foo.bar' notation " .
                "without 'Controller' and 'Action' suffixes", $call,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        if (!class_exists($controller)) {
            throw new ControllerException(
                'No controller found such %q', $controller,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        } elseif (!method_exists($controller, $action)) {
            throw new ControllerException(
                'No controller action found such \'%s::%s()\'', [$controller, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        return (new $controller($this->app))->call($action, $actionParams);
    }

    /**
     * Redirect client to given location applying `$toArgs` if provided, with given headers & cookies.
     *
     * @param  string     $to
     * @param  array|null $toArgs
     * @param  int        $code
     * @param  mixed      $body
     * @param  array|null $headers
     * @param  array|null $cookies
     * @return void
     */
    public final function redirect(string $to, array $toArgs = null, int $code = Status::FOUND,
        mixed $body = null, array $headers = null, array $cookies = null): void
    {
        $toArgs && $to = vsprintf($to, $toArgs);

        // In case, not most.
        if ($body !== null) {
            $this->response->setBody($body);
        }

        $this->response->redirect($to, $code, $headers, $cookies);
    }

    /**
     * Flash for session.
     *
     * @param  mixed|null $message
     * @return mixed|null (Session)
     */
    public final function flash(mixed $message = null): mixed
    {
        return func_num_args() ? $this->session->flash($message) : $this->session->flash();
    }

    /**
     * Get request object.
     *
     * @return froq\http\Request
     */
    public final function request(): Request
    {
        return $this->request;
    }

    /**
     * Get response object, setting status & body content & content attributes if provided.
     *
     * @param  int|null   $code
     * @param  mixed|null $content
     * @param  array|null $attributes
     * @return froq\http\Response
     */
    public final function response(int $code = null, mixed $content = null, array $attributes = null): Response
    {
        if (func_num_args()) {
            isset($code) && $this->response->setStatus($code);
            isset($content) && $this->response->setContent($content);
            isset($attributes) && $this->response->setContentAttributes($attributes);
        }

        return $this->response;
    }

    /**
     * Create a payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\Payload
     */
    public final function payload(int $code, mixed $content, array $attributes = null): Payload
    {
        return new Payload($code, $content, $attributes);
    }

    /**
     * Create a HTML payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  string     $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\HtmlPayload
     */
    public final function htmlPayload(int $code, string $content, array $attributes = null): HtmlPayload
    {
        return new HtmlPayload($code, $content, $attributes);
    }

    /**
     * Create a plain payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  string     $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\PlainPayload
     */
    public final function plainPayload(int $code, string $content, array $attributes = null): PlainPayload
    {
        return new PlainPayload($code, $content, $attributes);
    }

    /**
     * Create a JSON payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\JsonPayload
     */
    public final function jsonPayload(int $code, mixed $content, array $attributes = null): JsonPayload
    {
        return new JsonPayload($code, $content, $attributes);
    }

    /**
     * Create a XML payload with given status & content, with/without content attributes.
     *
     * @param  int          $code
     * @param  array|string $content
     * @param  array|null   $attributes
     * @return froq\http\response\payload\XmlPayload
     */
    public final function xmlPayload(int $code, array|string $content, array $attributes = null): XmlPayload
    {
        return new XmlPayload($code, $content, $attributes);
    }

    /**
     * Create an image payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  string     $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\ImagePayload
     */
    public final function imagePayload(int $code, string $content, array $attributes = null): ImagePayload
    {
        return new ImagePayload($code, $content, $attributes);
    }

    /**
     * Create a file payload with given status & content, with/without content attributes.
     *
     * @param  int        $code
     * @param  string     $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\FilePayload
     */
    public final function filePayload(int $code, string $content, array $attributes = null): FilePayload
    {
        return new FilePayload($code, $content, $attributes);
    }

    /**
     * Get a URI segment.
     *
     * @param  int|string  $key
     * @param  string|null $default
     * @return string|null
     */
    public final function segment(int|string $key, string $default = null): string|null
    {
        return $this->request->segment($key, $default);
    }

    /**
     * Get all/many URI segments or Segments object.
     *
     * @param  array<int|string>  $keys
     * @param  array<string>|null $defaults
     * @return array<string>|froq\http\request\Segments
     */
    public final function segments(array $keys = null, array $defaults = null): array|Segments
    {
        return $this->request->segments($keys, $defaults);
    }

    /**
     * Get a segment param.
     *
     * @param  string      $name
     * @param  string|null $default
     * @return string|null
     */
    public final function segmentParam(string $name, string $default = null): string|null
    {
        return $this->request->segmentParam($name, $default);
    }

    /**
     * Get many segment params.
     *
     * @param  array<string>|null $names
     * @param  array<string>|null $defaults
     * @return array<string>|null
     */
    public final function segmentParams(array $names = null, array $defaults = null): array|null
    {
        return $this->request->segmentParams($names, $defaults);
    }

    /**
     * Get one $_GET param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public final function getParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return $this->request->getParam($name, $default, ...$options);
    }

    /**
     * Get many/all $_GET params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public final function getParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return $this->request->getParams($names, $defaults, ...$options);
    }

    /**
     * Get one $_POST param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public final function postParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return $this->request->postParam($name, $default, ...$options);
    }

    /**
     * Get many/all $_POST params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public final function postParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return $this->request->postParams($names, $defaults, ...$options);
    }

    /**
     * Get one $_COOKIE param.
     *
     * @param  string     $name
     * @param  mixed|null $default
     * @param  mixed   ...$options
     * @return mixed
     */
    public final function cookieParam(string $name, mixed $default = null, mixed ...$options): mixed
    {
        return $this->request->cookieParam($name, $default, ...$options);
    }

    /**
     * Get many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $names
     * @param  array|null                $defaults
     * @param  mixed                  ...$options
     * @return array
     */
    public final function cookieParams(array $names = null, array $defaults = null, mixed ...$options): array
    {
        return $this->request->cookieParams($names, $defaults, ...$options);
    }

    /**
     * Call an action that defined in subclass or by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", "Book.show")`.
     *
     * @param  string $action
     * @param  array  $actionParams
     * @param  bool   $suffix
     * @return mixed
     * @throws froq\app\ControllerException
     */
    public final function call(string $action, array $actionParams = [], bool $suffix = false): mixed
    {
        // For short calls (eg: call('foo') instead call('fooAction')).
        if ($suffix && ($action !== Controller::INDEX_ACTION && $action !== Controller::ERROR_ACTION)) {
            $action .= Controller::ACTION_SUFFIX;
        }

        $this->action       = $action;
        $this->actionParams = &$actionParams; // Keep originals, allow mutations (&) if before() exists.

        try {
            $ref = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            throw new ControllerException(
                'No action exists such \'%s::%s()\'', [static::class, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException(cause: $e)
            );
        }

        // Call before action if exists.
        $this->before && $this->before();

        $params     = $this->prepareActionParams($ref, $actionParams);
        $paramsRest = array_values($actionParams);

        // Call action, merging with originals as rest (eg: fooAction($id, ...$rest)).
        $return = $this->$action(...[...$params, ...$paramsRest]);

        // Call after action if exists.
        $this->after && $this->after();

        return $return;
    }

    /**
     * Call an callable (function) that defined by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", function ($id) { .. })`.
     *
     * @param  callable $action
     * @param  array    $actionParams
     * @return mixed
     * @throws froq\app\ControllerException
     */
    public final function callCallable(callable $action, array $actionParams = []): mixed
    {
        $this->action       = Controller::NAME_CLOSURE;
        $this->actionParams = &$actionParams; // Keep originals, allow mutations (&) if before() exists.

        // Make "$this" available in called action.
        $action = $action->bindTo($this, $this);

        try {
            $ref = new ReflectionFunction($action);
        } catch (ReflectionException $e) {
            throw new ControllerException(
                'No callable exists such \'%s::%s()\'', [static::class, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException(cause: $e)
            );
        }

        // Call before action if exists.
        $this->before && $this->before();

        $params     = $this->prepareActionParams($ref, $actionParams);
        $paramsRest = array_values($actionParams);

        // Call action, merging with originals as rest (eg: fooAction($id, ...$rest)).
        $return = $action(...[...$params, ...$paramsRest]);

        // Call after action if exists.
        $this->after && $this->after();

        return $return;
    }

    /**
     * Initialize a controller object by given controller name/controller class name, throw a
     * `ControllerException` if no such controller class exists.
     *
     * @param  string $class
     * @return froq\app\Controller
     * @throws froq\app\ControllerException
     */
    public final function initController(string $class): Controller
    {
        $class = trim($class, '\\');

        // If no full class name given.
        if (!str_contains($class, '\\')) {
            $class = Controller::NAMESPACE . '\\' . ucfirst($class) . Controller::SUFFIX;
        }

        class_exists($class) || throw new ControllerException(
            'Controller class %q not exists', $class
        );
        class_extends($class, Controller::class) || throw new ControllerException(
            'Controller class %q must extend class %q', [$class, Controller::class]
        );

        return new $class($this->app);
    }

    /**
     * Initialize a repository object by given repository name or repository class name, throw a
     * `ControllerException` if no such repository class exists.
     *
     * @param  string                      $class
     * @param  froq\app\Controller|null    $controller
     * @param  froq\database\Database|null $database
     * @return froq\app\Repository
     * @throws froq\app\ControllerException
     */
    public final function initRepository(string $class, Controller $controller = null, Database $database = null): Repository
    {
        $class = trim($class, '\\');

        // If no full class name given.
        if (!str_contains($class, '\\')) {
            $class = Repository::NAMESPACE . '\\' . ucfirst($class) . Repository::SUFFIX;
        }

        class_exists($class) || throw new ControllerException(
            'Repository class %q not exists', $class
        );
        class_extends($class, Repository::class) || throw new ControllerException(
            'Repository class %q must extend class %q', [$class, Repository::class]
        );

        return new $class($controller ?? $this, $database ?? $this->app->database);
    }

    /**
     * Create a `HttpException` instance by given code.
     *
     * If a `froq\http\exception\client` or `froq\http\exception\server` is exists by given (status) code, then
     * that exception's instance will be returned (eg: froq\http\exception\client\NotFoundException for 404 code),
     * otherwise `froq\http\HttpException` will be returned.
     *
     * @param  int         $code
     * @param  string|null $message
     * @param  mixed|null  $messageParams
     * @return froq\http\HttpException
     */
    public final function createHttpException(int $code, string $message = null, mixed $messageParams = null): HttpException
    {
        if ($code >= 400 && $code <= 599) {
            $name = Status::getTextByCode($code);
            if ($name) {
                $class = sprintf(
                    'froq\http\exception\%s\%sException',
                    $code < 500 ? 'client' : 'server', // Class type.
                    preg_replace('~[\W]~', '', $name), // Class name.
                );

                if (class_exists($class)) {
                    // These classes don't use codes, they have already.
                    return new $class($message, $messageParams);
                }
            }
        }

        return new HttpException($message, $messageParams, code: $code);
    }

    /**
     * Prepare an action's parameters to fulfill its required/non-required parameters needed on
     * calltime/runtime.
     */
    private function prepareActionParams(ReflectionMethod|ReflectionFunction $ref, array $actionParams): array
    {
        $ret = [];

        foreach ($ref->getParameters() as $i => $param) {
            if ($param->isVariadic()) {
                continue;
            }

            // Action parameter can be named or indexed, also have a default value.
            $value = $actionParams[$param->name] ?? $actionParams[$i] ?? (
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );

            if ($param->hasType()) {
                $type = $param->getType();
                // Only built-ins / scalars will be casted.
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()
                    && preg_test('~int|float|string|bool~', $type->getName())) {
                    settype($value, $type->getName());
                }
            }

            $ret[] = $value;
        }

        return $ret;
    }
}
