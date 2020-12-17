<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ControllerException, View, Model};
use froq\http\{Request, Response, request\Segments, response\Status};
use froq\http\response\payload\{Payload, JsonPayload, XmlPayload, HtmlPayload, FilePayload, ImagePayload};
use froq\{App, Router, session\Session, database\Database, common\objects\Registry};
use Throwable, Reflector, ReflectionMethod, ReflectionFunction, ReflectionException;

/**
 * Controller.
 *
 * Represents a controller entity which is a part of MVC stack.
 *
 * @package froq\mvc
 * @object  froq\mvc\Controller
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class Controller
{
    /**
     * Namespace.
     * @const string
     */
    public const NAMESPACE      = 'app\controller';

    /**
     * Defaults.
     * @const string
     */
    public const DEFAULT        = 'app\controller\IndexController',
                 DEFAULT_SHORT  = 'IndexController',
                 ACTION_DEFAULT = 'index';

    /**
     * Suffixes.
     * @const string
     */
    public const SUFFIX         = 'Controller',
                 ACTION_SUFFIX  = 'Action';

    /**
     * Special actions.
     * @const string
     */
    public const INDEX_ACTION   = 'index',
                 ERROR_ACTION   = 'error';

    /**
     * Name ids.
     * @const string
     */
    public const NAME_DEFAULT   = '@default',
                 NAME_CLOSURE   = '@closure';

    /**
     * App.
     * @var froq\App
     */
    protected App $app;

    /**
     * Request.
     * @var froq\http\Request
     */
    protected Request $request;

    /**
     * Response.
     * @var froq\http\Response
     */
    protected Response $response;

    /**
     * Name.
     * @var string
     */
    private string $name;

    /**
     * Action.
     * @var string
     */
    private string $action;

    /**
     * Action params.
     * @var array<any>
     */
    private array $actionParams;

    /**
     * View.
     * @var froq\mvc\View
     */
    protected View $view;

    /**
     * Model.
     * @var froq\mvc\Model
     */
    protected Model $model;

    /**
     * Use view.
     * @var bool
     */
    public bool $useView = false;

    /**
     * Use model.
     * @var bool
     */
    public bool $useModel = false;

    /**
     * Use session.
     * @var bool
     */
    public bool $useSession = false;

    /**
     * Before/after.
     * @var bool,bool
     * @since 4.9
     */
    private bool $before = false, $after = false;

    /**
     * Constructor.
     *
     * @param froq\App $app
     */
    public final function __construct(App $app)
    {
        $this->app = $app;

        // Copy as a shortcut for child classes.
        $this->request = $app->request();
        $this->response = $app->response();

        // Load usings.
        $this->useView && $this->loadView();
        $this->useModel && $this->loadModel();
        $this->useSession && $this->loadSession();

        // Call init() method if defined in child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store (as last) controller.
        Registry::set('@controller', $this, false);

        // Set before/after ticks these called in call() method.
        $this->before = method_exists($this, 'before');
        $this->after = method_exists($this, 'after');
    }

    /**
     * Get app.
     *
     * @return froq\App
     */
    public final function getApp(): App
    {
        return $this->app;
    }

    /**
     * Get request.
     *
     * @return froq\http\Request
     */
    public final function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response.
     *
     * @return froq\http\Response
     */
    public final function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get view.
     *
     * @return ?froq\mvc\View
     */
    public final function getView(): ?View
    {
        return $this->view ?? null;
    }

    /**
     * Get model.
     *
     * @return ?froq\mvc\Model
     */
    public final function getModel(): ?Model
    {
        return $this->model ?? null;
    }

    /**
     * Gets the name of controller that run at the time, creating if not set yet.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this->name ??= substr(strrchr(static::class, '\\'), 1);
    }

    /**
     * Gets the short name of controller that run at the time.
     *
     * @return string
     */
    public final function getShortName(): string
    {
        $name = $this->getName();

        if (strsfx($name, self::SUFFIX)) {
            $name = substr($name, 0, -strlen(self::SUFFIX));
        }

        return $name;
    }

    /**
     * Gets the action name that called at the time.
     *
     * @return string
     */
    public final function getActionName(): string
    {
        return $this->action ?? '';
    }

    /**
     * Gets the action short name that called at the time.
     *
     * @return string
     */
    public final function getActionShortName(): string
    {
        $action = $this->getActionName();

        if (strsfx($action, self::ACTION_SUFFIX)) {
            $action = substr($action, 0, -strlen(self::ACTION_SUFFIX));
        }

        return $action;
    }

    /**
     * Gets the action params that called at the time.
     *
     * @return array
     */
    public final function getActionParams(): array
    {
        return $this->actionParams ?? [];
    }

    /**
     * Gets the current controller path built with action that called at the time.
     *
     * @return string
     */
    public final function getPath(): string
    {
        return $this->getShortName() .'.'. $this->getActionShortName();
    }

    /**
     * Loads (initializes) the view object for the owner controller if controller's `$useView`
     * property set to true. Throws a `ControllerException` if no `view.layout` option found in
     * configuration.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadView(): void
    {
        if (!isset($this->view)) {
            $layout = $this->app->config('view.layout');

            if (!$layout) {
                throw new ControllerException("No 'view.layout' option found in config");
            }

            $this->view = new View($this);
            $this->view->setLayout($layout);
        }
    }

    /**
     * Loads (initializes) the model object for the owner controller if controller's `$useModel`
     * property set to true. Throws a `ControllerException` if no such model class found.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadModel(): void
    {
        if (!isset($this->model)) {
            $name = $this->getShortName();
            $config = $this->app->config('model');

            // Map may be defined in config (eg: ["Foo" => "app\foo\FooModel"])
            if (!empty($config['map'])) {
                foreach ($config['map'] as $controllerName => $class) {
                    if ($controllerName == $name) {
                        break;
                    }
                }
            }

            // Use found name in config map or self name.
            $class ??= ($config['namespace'] ?? Model::NAMESPACE) . '\\' . $name . Model::SUFFIX;

            $this->model = $this->initModel($class);
        }
    }

    /**
     * Loads (starts) the session object for the owner controller if controller's `$useSession`
     * property set to true. Throws a `ControllerException` if App has no session.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadSession(): void
    {
        $session = $this->app->session();

        if (!$session) {
            throw new ControllerException("App has no session object (check 'session' option in "
                . "config and be sure it is not null)");
        }

        $session->start();
    }

    /**
     * Gets an environment or a server var or returns default.
     *
     * @param  string   $name
     * @param  any|null $default
     * @return any
     */
    public final function env(string $name, $default = null)
    {
        // Uppers for nginx (in some cases).
        $value = $_ENV[$name] ?? $_ENV[strtoupper($name)] ??
                 $_SERVER[$name] ?? $_SERVER[strtoupper($name)] ?? null;

        if ($value === null) {
            if (($value = getenv($name)) === false) {
                if (($value = getenv(strtoupper($name))) === false) {
                    unset($value);
                }
            }
        }

        return $value ?? $default;
    }

    /**
     * Views a view file with given `$fileData` arguments if provided rendering the file
     * in a wrapped output buffer.
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
            throw new ControllerException("No '\$view' property set yet, be sure '\$useView' is "
                . "true in '%s' class", static::class);
        }

        // Shortcut for status (if given).
        if ($status) {
            $this->response->setStatus($status);
        }

        return $this->view->render($file, $fileData);
    }

    /**
     * Forwards an internal call to other call (controller method) with given call arguments. The
     * `$call` parameter must be fully qualified for explicit methods without `Controller` and
     * `Action` suffixes eg: `Book.show`, otherwise `index` method does not require that explicity.
     *
     * @param  string $call
     * @param  array  $callArgs
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function forward(string $call, array $callArgs = [])
    {
        [$controller, $action, $actionParams] = Router::prepare($call, $callArgs);

        if (!$controller || !$action) {
            throw new ControllerException("Invalid call directive given, use 'Foo.bar' "
                . "convention without 'Controller' and 'Action' suffixes", null, Status::NOT_FOUND);
        } elseif (!class_exists($controller)) {
            throw new ControllerException("No controller found such '%s'", $controller, Status::NOT_FOUND);
        } elseif (!method_exists($controller, $action)) {
            throw new ControllerException("No controller action found such '%s::%s()'", [$controller, $action],
                Status::NOT_FOUND);
        }

        return (new $controller($this->app))->call($action, $actionParams ?? []);
    }

    /**
     * Redirects to given location applying `$toArgs` if provided, with given headers & cookies.
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
        if ($toArgs) $to = vsprintf($to, $toArgs);

        $this->response->redirect($to, $code, $headers, $cookies);
    }

    /**
     * Sets response (status) code.
     *
     * @param  int $code
     * @return self
     */
    public final function setResponseCode(int $code): self
    {
        $this->response->setStatus($code);

        return $this;
    }

    /**
     * Sets response (content) type.
     *
     * @param  string $type
     * @return self
     */
    public final function setResponseType(string $type): self
    {
        $this->response->setContentType($type);

        return $this;
    }

    /**
     * Status (alias of setResponseCode()).
     *
     * @param  int $code
     * @return self
     */
    public final function status(int $code): self
    {
        return $this->setResponseCode($code);
    }

    /**
     * Request (gets request object).
     *
     * @return froq\http\Request
     * @since  4.1
     */
    public final function request(): Request
    {
        return $this->request;
    }

    /**
     * Gets response object, sets response status & body content, also content attributes if provided.
     *
     * @param  int|null   $code
     * @param  any|null   $content
     * @param  array|null $attributes
     * @return froq\http\Response
     */
    public final function response(int $code = null, $content = null, array $attributes = null): Response
    {
        $response = $this->response;

        // Content can be null, but not code.
        if ($code !== null) {
            $response->setStatus($code)->setBody($content, $attributes);
        }

        return $response;
    }

    /**
     * Gets App's Session object.
     *
     * @return ?froq\session\Session
     * @since  4.2
     */
    public final function session(): ?Session
    {
        return $this->app->session();
    }

    /**
     * Gets App's Database object.
     *
     * @return ?froq\database\Database
     * @since  4.2
     */
    public final function database(): ?Database
    {
        return $this->app->database();
    }

    /**
     * Yields a payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\Payload
     */
    public final function payload(int $code, $content, array $attributes = null): Payload
    {
        return new Payload($code, $content, $attributes);
    }

    /**
     * Yields a JSON payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\JsonPayload
     */
    public final function jsonPayload(int $code, $content, array $attributes = null): JsonPayload
    {
        return new JsonPayload($code, $content, $attributes);
    }

    /**
     * Yields a XML payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\XmlPayload
     */
    public final function xmlPayload(int $code, $content, array $attributes = null): XmlPayload
    {
        return new XmlPayload($code, $content, $attributes);
    }

    /**
     * Yields a HTML payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\HtmlPayload
     */
    public final function htmlPayload(int $code, $content, array $attributes = null): HtmlPayload
    {
        return new HtmlPayload($code, $content, $attributes);
    }

    /**
     * Yields a file payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\FilePayload
     */
    public final function filePayload(int $code, $content, array $attributes = null): FilePayload
    {
        return new FilePayload($code, $content, $attributes);
    }

    /**
     * Yields an image payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\ImagePayload
     */
    public final function imagePayload(int $code, $content, array $attributes = null): ImagePayload
    {
        return new ImagePayload($code, $content, $attributes);
    }

    /**
     * Gets a segment value.
     *
     * @param  int|string $key
     * @param  any        $default
     * @return any
     * @since  4.2
     */
    public final function segment($key, $default = null)
    {
        return $this->request->uri()->segment($key, $default);
    }

    /**
     * Gets URI's Segments object.
     *
     * @return ?froq\http\request\Segments
     * @since  4.2
     */
    public final function segments(): ?Segments
    {
        return $this->request->uri()->segments();
    }

    /**
     * Gets URI's Segments object as list.
     *
     * @param  int $offset
     * @return ?array
     * @since  4.4
     */
    public final function segmentsList(int $offset = 0): ?array
    {
        $segments = $this->request->uri()->segments();

        return $segments ? $segments->toList($offset) : null;
    }

    /**
     * Gets a get parameter.
     *
     * @param  string   $name
     * @param  any|null $default
     * @return any|null
     */
    public final function getParam(string $name, $default = null)
    {
        return $this->request->getParam($name, $default);
    }

    /**
     * Gets all get parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $default
     * @return array
     */
    public final function getParams(array $names = null, $default = null): array
    {
        return $this->request->getParams($names, $default);
    }

    /**
     * Gets a post parameter.
     *
     * @param  string   $name
     * @param  any|null $default
     * @return any|null
     */
    public final function postParam(string $name, $default = null)
    {
        return $this->request->postParam($name, $default);
    }

    /**
     * Gets all post parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $default
     * @return array
     */
    public final function postParams(array $names = null, $default = null): array
    {
        return $this->request->postParams($names, $default);
    }

    /**
     * Gets a cookie parameter.
     *
     * @param  string   $name
     * @param  any|null $default
     * @return any|null
     */
    public final function cookieParam(string $name, $default = null)
    {
        return $this->request->cookieParam($name, $default);
    }

    /**
     * Gets all cookie parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $default
     * @return array
     */
    public final function cookieParams(array $names = null, $default = null): array
    {
        return $this->request->cookieParams($names, $default);
    }

    /**
     * Calls an action that defined in subclass or by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", "Book.show")`.
     *
     * @param  string $action
     * @param  array  $actionParams
     * @param  bool   $suffix
     * @return any
     * @throws froq\mvc\ControllerException
     */
    public final function call(string $action, array $actionParams = [], bool $suffix = false)
    {
        // For short calls (eg: call('foo') instead call('fooAction')).
        if ($suffix && !in_array($action, [self::INDEX_ACTION, self::ERROR_ACTION])) {
            $action .= self::ACTION_SUFFIX;
        }

        $this->action       = $action;
        $this->actionParams = $actionParams; // Keep originals.

        try {
            $ref = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            throw new ControllerException($e, null, Status::NOT_FOUND);
        }

        $params     = $this->prepareActionParams($ref, $actionParams);
        $paramsRest = array_values($actionParams);

        // Merge with originals as rest params (eg: fooAction($id, ...$rest)).
        $params = [...$params, ...$paramsRest];

        try {
            $this->before && $this->before();   // Call if defined in child.
            $ret = $this->{$action}(...$params);
            $this->after && $this->after();     // Call if defined in child.
        } catch (Throwable $e) {
            $ret = method_exists($this, 'error') ? $this->error($e) : $this->app->error($e);
        }

        return $ret;
    }

    /**
     * Calls an callable (function) that defined by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", function ($id) { .. })`.
     *
     * @param  callable $action
     * @param  array    $actionParams
     * @return any
     * @throws froq\mvc\ControllerException
     */
    public final function callCallable(callable $action, array $actionParams = [])
    {
        $this->action       = self::NAME_CLOSURE;
        $this->actionParams = $actionParams; // Keep originals.

        // Make "$this" available in called action.
        $action = $action->bindTo($this, $this);

        try {
            $ref = new ReflectionFunction($action);
        } catch (ReflectionException $e) {
            throw new ControllerException($e, null, Status::NOT_FOUND);
        }

        $params     = $this->prepareActionParams($ref, $actionParams);
        $paramsRest = array_values($actionParams);

        // Merge with originals as rest params (eg: fooAction($id, ...$rest)).
        $params = [...$params, ...$paramsRest];

        try {
            $this->before && $this->before();   // Call if defined in child.
            $ret = $action(...$params);
            $this->after && $this->after();     // Call if defined in child.
        } catch (Throwable $e) {
            $ret = method_exists($this, 'error') ? $this->error($e) : $this->app->error($e);
        }

        return $ret;
    }

    /**
     * Initializes a model object by given model/model class name. Throws a `ControllerException`
     * if no such model class exists.
     *
     * @param  string                      $name
     * @param  froq\mvc\Controller|null    $controller
     * @param  froq\database\Database|null $database
     * @return froq\mvc\Model (static)
     * @since  4.13
     */
    public final function initModel(string $class, Controller $controller = null, Database $database = null): Model
    {
        $class = trim($class, '\\');

        // If no full class name given.
        str_contains($class, '\\') || (
            $class = $this->app->config('model.namespace', Model::NAMESPACE)
                . '\\' . $class . Model::SUFFIX
        );

        if (!class_exists($class)) {
            throw new ControllerException('Model class `%s` not exists', $class);
        } elseif (!class_extends($class, Model::class)) {
            throw new ControllerException('Model class `%s` must extend class `%s`', [$class, Model::class]);
        }

        return new $class($controller ?? $this, $database ?? $this->database());
    }

    /**
     * Prepares an action's parameters to fulfill its required/non-required parameters needed on
     * calltime/runtime.
     *
     * @param  Reflector $reflector
     * @param  array     $actionParams
     * @return array
     */
    private function prepareActionParams(Reflector $reflector, array $actionParams): array
    {
        $ret = [];

        foreach ($reflector->getParameters() as $i => $param) {
            if ($param->isVariadic()) {
                continue;
            }

            // Action parameter can be named or indexed.
            $ret[] = $actionParams[$param->name] ?? $actionParams[$i] ?? (
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }

        return $ret;
    }
}
