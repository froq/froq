<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\http\{Request, Response, HttpException, request\Segments, response\Status,
    response\payload\Payload, response\payload\JsonPayload, response\payload\XmlPayload,
    response\payload\HtmlPayload, response\payload\FilePayload, response\payload\ImagePayload,
    response\payload\PlainPayload, exception\client\NotFoundException};
use froq\{App, Router, session\Session, database\Database, util\Objects, util\misc\System};
use ReflectionMethod, ReflectionFunction, ReflectionNamedType, ReflectionException;

/**
 * Controller.
 *
 * A class, part of MVC stack and extended by other `app\controller` classes.
 *
 * @package froq\mvc
 * @object  froq\mvc\Controller
 * @author  Kerem Güneş
 * @since   4.0
 */
class Controller
{
    /** @const string */
    public final const NAMESPACE      = 'app\controller';

    /** @const string */
    public final const DEFAULT        = 'app\controller\IndexController',
                       DEFAULT_SHORT  = 'IndexController',
                       ACTION_DEFAULT = 'index';

    /** @const string */
    public final const SUFFIX         = 'Controller',
                       ACTION_SUFFIX  = 'Action';

    /** @const string */
    public final const INDEX_ACTION   = 'index',
                       ERROR_ACTION   = 'error';

    /** @const string */
    public final const NAME_DEFAULT   = '@default',
                       NAME_CLOSURE   = '@closure';

    /** @var froq\App */
    public readonly App $app;

    /** @var froq\http\Request */
    public readonly Request $request;

    /** @var froq\http\Response */
    public readonly Response $response;

    /** @var froq\mvc\View */
    public readonly View $view;

    /** @var froq\mvc\Model */
    public readonly Model $model;

    /** @var froq\session\Session */
    public readonly Session $session;

    /** @var bool */
    public bool $useView = false;

    /** @var bool */
    public bool $useModel = false;

    /** @var bool */
    public bool $useSession = false;

    /** @var string */
    private string $action;

    /** @var array<mixed> */
    private array $actionParams;

    /** @var bool, bool */
    private bool $before = false, $after = false;

    /**
     * Constructor.
     *
     * @param  froq\App|null $app
     * @throws froq\mvc\ControllerException
     */
    public final function __construct(App $app = null)
    {
        // Try active app object if none given.
        $this->app = $app ?? (
            function_exists('app') ? app()
                : throw new ControllerException('No app object to deal')
        );

        // Copy as a shortcut for subclasses.
        $this->request  = $this->app->request();
        $this->response = $this->app->response();

        // Load usings.
        $this->useView    && $this->loadView();
        $this->useModel   && $this->loadModel();
        $this->useSession && $this->loadSession();

        // Call init() method if defined in subclass.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store this controller (as last controller).
        $this->app::registry()::set('@controller', $this, false);

        // Set before/after ticks these called in call()/callCallable() methods.
        $this->before = method_exists($this, 'before');
        $this->after  = method_exists($this, 'after');
    }

    /**
     * Get view.
     *
     * @return froq\mvc\View|null
     */
    public final function getView(): View|null
    {
        return $this->view ?? null;
    }

    /**
     * Get model.
     *
     * @return froq\mvc\Model|null
     */
    public final function getModel(): Model|null
    {
        return $this->model ?? null;
    }

    /**
     * Get session.
     *
     * @return froq\session\Session
     * @since  6.0
     */
    public final function getSession(): Session|null
    {
        return $this->session ?? null;
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
     * Set an action param by given name/value.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     * @since  5.0
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
     * Check an action param's existence.
     *
     * @param  string $name
     * @return bool
     * @since  5.0
     */
    public final function hasActionParam(string $name): bool
    {
        return isset($this->actionParams[$name]);
    }

    /**
     * Set action params by given name/value order.
     *
     * @param  array<string, mixed> $params
     * @return void
     * @since  5.0
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
     * Check all action param's existence, or given names only.
     *
     * @param  array<string>|null $names
     * @return bool
     * @since  5.0
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
     * Load (initialize) the view object for the owner controller if controller's `$useView` property
     * set to true and `$view` property is not set yet, throw a `ControllerException` if no `view.layout`
     * option found in configuration.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadView(): void
    {
        if (!isset($this->view)) {
            $layout = $this->app->config('view.layout');
            $layout || throw new ControllerException(
                'No `view.layout` option found in config'
            );

            $this->view = new View($this);
            $this->view->setLayout($layout);
        }
    }

    /**
     * Load (initialize) the model object for the owner controller if controller's `$useModel` property
     * set to true and `$model` property is not set yet.
     *
     * @return void
     */
    public final function loadModel(): void
    {
        if (!isset($this->model)) {
            $name = $this->getShortName();
            $base = null;

            // Check whether controller is a subcontroller.
            if (substr_count($controller = static::class, '\\') > 2) {
                $base = substr($controller, 0, strrpos($controller, '\\'));
                $base = substr($base, strrpos($base, '\\') + 1);
            }

            $class = !$base ? Model::NAMESPACE . '\\' . $name . Model::SUFFIX
                            : Model::NAMESPACE . '\\' . $base . '\\' . $name . Model::SUFFIX;

            // Try to use parent's model class if parent using model.
            if (!class_exists($class)) {
                $parent = get_parent_class($this);
                while ($parent && $parent != self::class) {
                    // Make model's class name fully qualified.
                    $class = str_replace(Controller::NAMESPACE, Model::NAMESPACE, Objects::getNamespace($parent))
                        . '\\' . (substr(Objects::getShortName($parent), 0, -strlen(Controller::SUFFIX)) . Model::SUFFIX);

                    // Validate existence & break.
                    if (class_exists($class)) {
                        break;
                    }

                    $parent = get_parent_class($parent);
                }
            }

            $this->model = $this->initModel($class);
        }
    }

    /**
     * Load (starts) the session object for the owner controller if controller's `$useSession` property
     * set to true, throw a `ControllerException` if App has no session.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadSession(): void
    {
        if (!isset($this->session)) {
            $session = $this->app->session();
            $session || throw new ControllerException(
                'App has no session object, be sure `session` option is not empty'
            );

            // @cancel: Must be started on-demand in actions or init method.
            // $session->start();

            $this->session = $session;
        }
    }

    /**
     * Get an env/server var, or return default.
     *
     * @param  string     $option
     * @param  mixed|null $default
     * @param  bool       $server
     * @return mixed|null
     */
    public final function env(string $option, mixed $default = null, bool $server = true): mixed
    {
        return System::envGet($option, $default, $server);
    }

    /**
     * View a view file with given `$fileData` arguments if provided, rendering the file in a wrapped output
     * buffer.
     *
     * @param  string     $file
     * @param  array|null $fileData
     * @param  int|null   $status
     * @return string
     * @throws froq\mvc\ControllerException
     */
    public final function view(string $file, array $fileData = null, int $status = null): string
    {
        if (!isset($this->view)) {
            throw new ControllerException(
                'No `$view` property set yet, be sure `$useView` is true in class `%s`',
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
     * @throws froq\mvc\ControllerException
     */
    public final function forward(string $call, array $callArgs = []): mixed
    {
        [$controller, $action, $actionParams] = Router::prepare($call, $callArgs);

        if (!$controller || !$action) {
            throw new ControllerException(
                'Invalid call directive `%s`, use `Foo.bar` notation ' .
                'without `Controller` and `Action` suffixes', $call,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        $class = new \XClass($controller);

        if (!$class->exists()) {
            throw new ControllerException(
                'No controller found such `%s`', $controller,
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        } elseif (!$class->methodExists($action)) {
            throw new ControllerException(
                'No controller action found such `%s::%s()`', [$controller, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        return $class->init($this->app)->call($action, $actionParams);
    }

    /**
     * Redirect client to given location applying `$toArgs` if provided, with given headers & cookies.
     *
     * @param  string     $to
     * @param  array|null $toArgs
     * @param  int        $code
     * @param  array|null $headers
     * @param  array|null $cookies
     * @return void
     */
    public final function redirect(string $to, array $toArgs = null, int $code = Status::FOUND,
        array $headers = null, array $cookies = null): void
    {
        $toArgs && $to = vsprintf($to, $toArgs);

        $this->response->redirect($to, $code, $headers, $cookies);
    }

    /**
     * Flash for session.
     *
     * @param  mixed|null $message
     * @return mixed|null (Session)
     * @since  6.0
     */
    public final function flash(mixed $message = null): mixed
    {
        return func_num_args() ? $this->session->flash($message) : $this->session->flash();
    }

    /**
     * Get request object.
     *
     * @return froq\http\Request
     * @since  4.1
     */
    public final function request(): Request
    {
        return $this->request;
    }

    /**
     * Get response object, set response status & body content, also content attributes when provided.
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
     * Yield a payload with given status & content, also content attributes if provided.
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
     * Yield a JSON payload with given status & content, also content attributes if provided.
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
     * Yield a XML payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\XmlPayload
     */
    public final function xmlPayload(int $code, mixed $content, array $attributes = null): XmlPayload
    {
        return new XmlPayload($code, $content, $attributes);
    }

    /**
     * Yield a HTML payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\HtmlPayload
     */
    public final function htmlPayload(int $code, mixed $content, array $attributes = null): HtmlPayload
    {
        return new HtmlPayload($code, $content, $attributes);
    }

    /**
     * Yield a file payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\FilePayload
     */
    public final function filePayload(int $code, mixed $content, array $attributes = null): FilePayload
    {
        return new FilePayload($code, $content, $attributes);
    }

    /**
     * Yield an image payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\ImagePayload
     */
    public final function imagePayload(int $code, mixed $content, array $attributes = null): ImagePayload
    {
        return new ImagePayload($code, $content, $attributes);
    }

    /**
     * Yield a plain payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  mixed      $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\PlainPayload
     */
    public final function plainPayload(int $code, mixed $content, array $attributes = null): PlainPayload
    {
        return new PlainPayload($code, $content, $attributes);
    }

    /**
     * Get a URI segment.
     *
     * @param  int|string  $key
     * @param  string|null $default
     * @return string|null
     * @since  4.2
     */
    public final function segment(int|string $key, string $default = null): string|null
    {
        return $this->request->uri->segment($key, $default);
    }

    /**
     * Get all/many URI segments or Segments object.
     *
     * @param  array<int|string>  $keys
     * @param  array<string>|null $defaults
     * @return array<string>|froq\http\request\Segments
     * @since  4.2
     */
    public final function segments(array $keys = null, array $defaults = null): array|Segments
    {
        return $this->request->uri->segments($keys, $defaults);
    }

    /**
     * Get URI segments object as list.
     *
     * @return array
     * @since  4.4
     */
    public final function segmentsList(): array
    {
        return $this->request->uri->segments->list();
    }

    /**
     * Get a segment param.
     *
     * @param  string      $name
     * @param  string|null $default
     * @return string|null
     * @since  5.0
     */
    public final function segmentParam(string $name, string $default = null): string|null
    {
        return $this->request->uri->segments->getParam($name, $default);
    }

    /**
     * Get many segment params.
     *
     * @param  array<string>|null $names
     * @param  array<string>|null $defaults
     * @return array<string>|null
     * @since  5.0
     */
    public final function segmentParams(array $names = null, array $defaults = null): array|null
    {
        return $this->request->uri->segments->getParams($names, $defaults);
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
     * @throws froq\mvc\ControllerException
     */
    public final function call(string $action, array $actionParams = [], bool $suffix = false): mixed
    {
        // For short calls (eg: call('foo') instead call('fooAction')).
        if ($suffix && ($action != Controller::INDEX_ACTION && $action != Controller::ERROR_ACTION)) {
            $action .= Controller::ACTION_SUFFIX;
        }

        $this->action       = $action;
        $this->actionParams =& $actionParams; // Keep originals, allow mutations (&) if before() exists.

        try {
            $ref = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            throw new ControllerException(
                'No action exists such `%s::%s()`', [static::class, $action],
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
     * @throws froq\mvc\ControllerException
     */
    public final function callCallable(callable $action, array $actionParams = []): mixed
    {
        $this->action       = Controller::NAME_CLOSURE;
        $this->actionParams =& $actionParams; // Keep originals, allow mutations (&) if before() exists.

        // Make "$this" available in called action.
        $action = $action->bindTo($this, $this);

        try {
            $ref = new ReflectionFunction($action);
        } catch (ReflectionException $e) {
            throw new ControllerException(
                'No callable exists such `%s::%s()`', [static::class, $action],
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
     * @return froq\mvc\Controller (static)
     * @throws froq\mvc\ControllerException
     * @since  5.0
     */
    public final function initController(string $class): Controller
    {
        $class = trim($class, '\\');

        // If no full class name given.
        if (!str_contains($class, '\\')) {
            $class = Controller::NAMESPACE . '\\' . ucfirst($class) . Controller::SUFFIX;
        }

        $class = new \XClass($class);

        $class->exists() || throw new ControllerException(
            'Controller class `%s` not exists', $class
        );
        $class->extends(Controller::class) || throw new ControllerException(
            'Controller class `%s` must extend class `%s`', [$class, Controller::class]
        );

        return $class->init($this->app);
    }

    /**
     * Initialize a model object by given model name /model class name, throw a `ControllerException`
     * if no such model class exists.
     *
     * @param  string                      $class
     * @param  froq\mvc\Controller|null    $controller
     * @param  froq\database\Database|null $database
     * @return froq\mvc\Model (static)
     * @throws froq\mvc\ControllerException
     * @since  4.13
     */
    public final function initModel(string $class, Controller $controller = null, Database $database = null): Model
    {
        $class = trim($class, '\\');

        // If no full class name given.
        if (!str_contains($class, '\\')) {
            $class = Model::NAMESPACE . '\\' . ucfirst($class) . Model::SUFFIX;
        }

        $class = new \XClass($class);

        $class->exists() || throw new ControllerException(
            'Model class `%s` not exists', $class
        );
        $class->extends(Model::class) || throw new ControllerException(
            'Model class `%s` must extend class `%s`', [$class, Model::class]
        );

        return $class->init($controller ?? $this, $database ?? $this->app->database());
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
     * @since  6.0
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

                // Note: These classes don't use code, so they have already.
                if (class_exists($class)) return new $class($message, $messageParams);
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
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    settype($value, $type->getName());
                }
            }

            $ret[] = $value;
        }

        return $ret;
    }
}
