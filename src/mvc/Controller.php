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
use froq\{App, Router, session\Session, database\Database, common\object\Registry};
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
    /** @const string */
    public const NAMESPACE      = 'app\controller';

    /** @const string */
    public const DEFAULT        = 'app\controller\IndexController',
                 DEFAULT_SHORT  = 'IndexController',
                 ACTION_DEFAULT = 'index';

    /** @const string */
    public const SUFFIX         = 'Controller',
                 ACTION_SUFFIX  = 'Action';

    /** @const string */
    public const INDEX_ACTION   = 'index',
                 ERROR_ACTION   = 'error';

    /** @const string */
    public const NAME_DEFAULT   = '@default',
                 NAME_CLOSURE   = '@closure';

    /** @var froq\App */
    protected App $app;

    /** @var froq\http\Request */
    protected Request $request;

    /** @var froq\http\Response */
    protected Response $response;

    /** @var string */
    private string $name;

    /** @var string */
    private string $action;

    /** @var array<any> */
    private array $actionParams;

    /** @var froq\mvc\View */
    protected View $view;

    /** @var froq\mvc\Model */
    protected Model $model;

    /** @var bool */
    public bool $useView = false;

    /** @var bool */
    public bool $useModel = false;

    /** @var bool */
    public bool $useSession = false;

    /** @var bool, bool @since 4.9 */
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
        $this->request  = $app->request();
        $this->response = $app->response();

        // Load usings.
        $this->useView    && $this->loadView();
        $this->useModel   && $this->loadModel();
        $this->useSession && $this->loadSession();

        // Call init() method if defined in child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store (as last) controller.
        Registry::set('@controller', $this, false);

        // Set before/after ticks these called in call() method.
        $this->before = method_exists($this, 'before');
        $this->after  = method_exists($this, 'after');
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
     * Get name of controller that run at the time, creating if not set yet.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this->name ??= substr(strrchr(static::class, '\\'), 1);
    }

    /**
     * Get short name of controller that run at the time.
     *
     * @return string
     */
    public final function getShortName(): string
    {
        $name = $this->getName();

        if (str_ends_with($name, self::SUFFIX)) {
            $name = substr($name, 0, -strlen(self::SUFFIX));
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
     * @return string
     */
    public final function getActionShortName(): string
    {
        $action = $this->getActionName();

        if (str_ends_with($action, self::ACTION_SUFFIX)) {
            $action = substr($action, 0, -strlen(self::ACTION_SUFFIX));
        }

        return $action;
    }

    /**
     * Get action params that called at the time.
     *
     * @return array
     */
    public final function getActionParams(): array
    {
        return $this->actionParams ?? [];
    }

    /**
     * Get current controller path built with action that called at the time.
     *
     * @return string
     */
    public final function getPath(): string
    {
        return $this->getShortName() . '.' . $this->getActionShortName();
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

            if (!$layout) {
                throw new ControllerException('No `view.layout` option found in config');
            }

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
            $name   = $this->getShortName();
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
     * Load (starts) the session object for the owner controller if controller's `$useSession` property
     * set to true, throw a `ControllerException` if App has no session.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadSession(): void
    {
        $session = $this->app->session();

        if ($session == null) {
            throw new ControllerException('App has no session object [tip: check `session` option in'
                . ' config and be sure it is not null]');
        }

        $session->start();
    }

    /**
     * Get an environment or a server var or return default.
     *
     * @param  string   $name
     * @param  any|null $default
     * @return any|null
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
     * View a view file with given `$fileData` arguments if provided, rendering the file in a wrapped output buffer.
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
            throw new ControllerException('No `$view` property set yet, be sure `$useView` is true on'
                . ' class %s', static::class);
        }

        // Shortcut for status.
        $status && $this->status($status);

        return $this->view->render($file, $fileData);
    }

    /**
     * Forward an internal call to other call (controller method) with given call arguments. The `$call`
     * parameter must be fully qualified for explicit methods without `Controller` and `Action` suffixes
     * eg: `Book.show`, otherwise `index` method does not require that explicity.
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
            throw new ControllerException('Invalid call directive %s, use `Foo.bar`'
                . ' convention without `Controller` and `Action` suffixes', $call, Status::NOT_FOUND);
        } elseif (!class_exists($controller)) {
            throw new ControllerException('No controller found such `%s`', $controller, Status::NOT_FOUND);
        } elseif (!method_exists($controller, $action)) {
            throw new ControllerException('No controller action found such `%s::%s()`', [$controller, $action],
                Status::NOT_FOUND);
        }

        return (new $controller($this->app))->call($action, $actionParams ?? []);
    }

    /**
     * Redirect clien to given location applying `$toArgs` if provided, with given headers & cookies.
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
     * Set response (status) code.
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
     * Set response (content) type.
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
     * Alias of setResponseCode().
     *
     * @param  int $code
     * @return self
     */
    public final function status(int $code): self
    {
        return $this->setResponseCode($code);
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
     * @param  any|null   $content
     * @param  array|null $attributes
     * @return froq\http\Response
     */
    public final function response(int $code = null, $content = null, array $attributes = null): Response
    {
        // Content can be null, but not code.
        if (func_num_args()) {
            $this->response
                 ->setStatus($code)
                 ->setBody($content, $attributes);
        }

        return $this->response;
    }

    /**
     * Get app session object.
     *
     * @return froq\session\Session|null
     * @since  4.2
     */
    public final function session(): Session|null
    {
        return $this->app->session();
    }

    /**
     * Gets app database object.
     *
     * @return froq\database\Database|null
     * @since  4.2
     */
    public final function database(): Database|null
    {
        return $this->app->database();
    }

    /**
     * Yield a payload with given status & content, also content attributes if provided.
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
     * Yield a JSON payload with given status & content, also content attributes if provided.
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
     * Yield a XML payload with given status & content, also content attributes if provided.
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
     * Yield a HTML payload with given status & content, also content attributes if provided.
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
     * Yield a file payload with given status & content, also content attributes if provided.
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
     * Yield an image payload with given status & content, also content attributes if provided.
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
     * Get a segment value.
     *
     * @param  int|string $key
     * @param  any|null   $default
     * @return any|null
     * @since  4.2
     */
    public final function segment(int|string $key, $default = null)
    {
        return $this->request->uri()->segment($key, $default);
    }

    /**
     * Get URI segments object.
     *
     * @return froq\http\request\Segments|null
     * @since  4.2
     */
    public final function segments(): Segments|null
    {
        return $this->request->uri()->segments();
    }

    /**
     * Get URI segments object as list.
     *
     * @param  int $offset
     * @return array|null
     * @since  4.4
     */
    public final function segmentsList(int $offset = 0): array|null
    {
        return $this->request->uri()->segments()?->toList($offset);
    }

    /**
     * Get a get parameter.
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
     * Get all get parameters or by given names only.
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
     * Get a post parameter.
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
     * Get all post parameters or by given names only.
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
     * Get a cookie parameter.
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
     * Get all cookie parameters or by given names only.
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
     * Call an action that defined in subclass or by `App.route()` method or other shortcut route
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
            $this->before && $this->before();
            $ret = $this->{$action}(...$params);
            $this->after  && $this->after();
        } catch (Throwable $e) {
            $ret = method_exists($this, 'error')
                 ? $this->error($e) : $this->app->error($e);
        }

        return $ret;
    }

    /**
     * Call an callable (function) that defined by `App.route()` method or other shortcut route
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
            $this->before && $this->before();
            $ret = $action(...$params);
            $this->after  && $this->after();
        } catch (Throwable $e) {
            $ret = method_exists($this, 'error')
                 ? $this->error($e) : $this->app->error($e);
        }

        return $ret;
    }

    /**
     * Initialize a model object by given model/model class name, throws a `ControllerException` if no
     * such model class exists.
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
     * Prepare an action's parameters to fulfill its required/non-required parameters needed on
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
