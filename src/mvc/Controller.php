<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ControllerException, View, Model};
use froq\http\{Request, Response, request\Uri, request\Segments, response\Status,
    response\payload\Payload, response\payload\JsonPayload, response\payload\XmlPayload,
    response\payload\HtmlPayload, response\payload\FilePayload, response\payload\ImagePayload,
    response\payload\PlainPayload, exception\client\NotFoundException};
use froq\{App, Router, session\Session, database\Database, util\Objects, util\misc\System};
use ReflectionMethod, ReflectionFunction, ReflectionNamedType, ReflectionException;

/**
 * Controller.
 *
 * Represents a controller entity which is a part of MVC stack.
 *
 * @package froq\mvc
 * @object  froq\mvc\Controller
 * @author  Kerem Güneş
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

    /** @var froq\mvc\View */
    protected View $view;

    /** @var froq\mvc\Model */
    protected Model $model;

    /** @var froq\mvc\Session */
    protected Session $session;

    /** @var string */
    protected string $modelClass;

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

        // Set to true as using model.
        if (isset($this->modelClass)) {
            $this->useModel = true;
        }
        // Or try to use parent's model class if using model.
        elseif (!isset($this->modelClass) && $this->useModel) {
            $parent = get_parent_class($this);
            while ($parent && $parent != self::class) {
                // Make model's class name fully qualified.
                $modelClass = str_replace(Controller::NAMESPACE, Model::NAMESPACE, Objects::getNamespace($parent))
                    . '\\' . (substr(Objects::getShortName($parent), 0, -strlen(Controller::SUFFIX)) . Model::SUFFIX);

                // Validate existence & break.
                if (class_exists($modelClass)) {
                    $this->modelClass = $modelClass;
                    break;
                }

                $parent = get_parent_class($parent);
            }
        }

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
     * Getter aliases.
     */
    public final function app() { return $this->getApp(); }
    public final function model() { return $this->getModel(); }

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
     * Get model class.
     *
     * @return string|null
     */
    public final function getModelClass(): string|null
    {
        return $this->modelClass ?? null;
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

        if (!$suffix && str_ends_with($name, self::SUFFIX)) {
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
     * @param  bool $suffix
     * @return string
     */
    public final function getActionShortName(bool $suffix = false): string
    {
        $action = $this->action ?? '';

        if (!$suffix && str_ends_with($action, self::ACTION_SUFFIX)) {
            $action = substr($action, 0, -strlen(self::ACTION_SUFFIX));
        }

        return $action;
    }

    /**
     * Set an action param by given name/value.
     *
     * @param  string $name
     * @param  any    $value
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
     * @return any|null
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
     * @param  array<string, any> $params
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
     * @param  bool               $combine
     * @return array
     */
    public final function getActionParams(array $names = null, bool $combine = false): array
    {
        $params = $this->actionParams ?? [];

        if ($names !== null) {
            $params = array_select($params, $names);
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

            if (!$layout) {
                throw new ControllerException(
                    'No `view.layout` option found in config'
                );
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
            // When no absolute class name given.
            if (!isset($this->modelClass)) {
                $name = $this->getShortName();
                $base = null;

                // Check whether controller is a sub-controller.
                if (substr_count($controller = static::class, '\\') > 2) {
                    $base = substr($controller, 0, strrpos($controller, '\\'));
                    $base = substr($base, strrpos($base, '\\') + 1);
                }

                $class = !$base ? Model::NAMESPACE . '\\' . $name . Model::SUFFIX
                                : Model::NAMESPACE . '\\' . $base . '\\' . $name . Model::SUFFIX;

                $this->modelClass = $class;
            }

            $this->model = $this->initModel($this->modelClass);
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

            if (!$session) {
                throw new ControllerException(
                    'App has no session object [tip: check `session` option in config ' .
                    'and be sure it is not null]'
                );
            }

            // @cancel: Must be started on-demand in actions or init.
            // $session->start();

            $this->session = $session;
        }
    }

    /**
     * Get request URI.
     *
     * @return froq\http\request\Uri
     */
    public final function uri(): Uri
    {
        return $this->request->uri();
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
     * buffer, or simply return view property when no arguments provided.
     *
     * @param  string|null $file
     * @param  array|null  $fileData
     * @param  int|null    $status
     * @return string|froq\mvc\View
     * @throws froq\mvc\ControllerException
     */
    public final function view(string $file = null, array $fileData = null, int $status = null): string|View
    {
        if (!isset($this->view)) {
            throw new ControllerException(
                'No `$view` property set yet, be sure `$useView` is true in class `%s`',
                static::class
            );
        }

        if (!func_num_args()) {
            return $this->view;
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
        } elseif (!$class->existsMethod($action)) {
            throw new ControllerException(
                'No controller action found such `%s::%s()`', [$controller, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException()
            );
        }

        return $class->init($this->app)->call($action, $actionParams);
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
     * Flash for session.
     *
     * @param  mixed|null $message
     * @return mixed|null (Session)
     * @since  6.0
     */
    public function flash(mixed $message = null): mixed
    {
        return func_num_args() ? $session->flash($message) : $session->flash();
    }

    /**
     * Set response status.
     *
     * @param  int $code
     * @return self
     */
    public final function status(int $code): self
    {
        $this->response->setStatus($code);

        return $this;
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
        if (func_num_args()) {
            isset($code) && $this->response->setStatus($code);
            isset($content) && $this->response->setContent($content);
            isset($attributes) && $this->response->setContentAttributes($attributes);
        }

        return $this->response;
    }

    /**
     * Get app's session object.
     *
     * @return froq\session\Session|null
     * @since  4.2
     */
    public final function session(): Session|null
    {
        return $this->app->session();
    }

    /**
     * Get app's database object.
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
     * Yield a plain payload with given status & content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $attributes
     * @return froq\http\response\payload\PlainPayload
     */
    public final function plainPayload(int $code, $content, array $attributes = null): PlainPayload
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
        return $this->request->segment($key, $default);
    }

    /**
     * Get all/many URI segments or Segments object.
     *
     * @param  array<int|string>  $keys
     * @param  array<string>|null $defaults
     * @return array<string>|froq\http\request\Segments|null
     * @since  4.2
     */
    public final function segments(array $keys = null, array $defaults = null): array|Segments|null
    {
        return $this->request->segments($keys, $defaults);
    }

    /**
     * Get URI segments object as list.
     *
     * @return array
     * @since  4.4
     */
    public final function segmentsList(): array
    {
        return $this->request->segments()->toList($offset);
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
        return $this->request->segments()->getParam($name, $default);
    }

    /**
     * Get many segment params.
     *
     * @param  array<string>|null $names
     * @param  array<string>|null $default
     * @return array<string>|null
     * @since  5.0
     */
    public final function segmentParams(array $names = null, array $default = null): array|null
    {
        return $this->request->segments()->getParams($names, $default);
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
        $this->actionParams =& $actionParams; // Keep originals, allow mutations (&) if before() exists.

        try {
            $ref = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            throw new ControllerException(
                'No action exists such `%s::%s()`', [static::class, $action],
                code: Status::NOT_FOUND, cause: new NotFoundException(cause: $e)
            );
        }

        try {
            $this->before && $this->before();

            $params     = $this->prepareActionParams($ref, $actionParams);
            $paramsRest = array_values($actionParams);

            // Call action, merging with originals as rest params (eg: fooAction($id, ...$rest)).
            $return = $this->{$action}(...[...$params, ...$paramsRest]);

            $this->after && $this->after();
        } catch (\Throwable $e) {
            // Log always, dev may skip errorLog().
            $this->app->errorLog($e);

            $return = method_exists($this, 'error')
                    ? $this->error($e) : $this->app->error($e);
        }

        return $return;
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

        try {
            $this->before && $this->before();

            $params     = $this->prepareActionParams($ref, $actionParams);
            $paramsRest = array_values($actionParams);

            // Call action, merging with originals as rest params (eg: fooAction($id, ...$rest)).
            $return = $action(...[...$params, ...$paramsRest]);

            $this->after && $this->after();
        } catch (\Throwable $e) {
            // Log always, dev may skip errorLog().
            $this->app->errorLog($e);

            $return = method_exists($this, 'error')
                    ? $this->error($e) : $this->app->error($e);
        }

        return $return;
    }

    /**
     * Initialize a controller object by given controller name/controller class name, throw a `ControllerException`
     * if no such controller class exists.
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
     * Initialize a model object by given model name /model class name, throw a `ControllerException` if no such
     * model class exists.
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

        return $class->init($controller ?? $this, $database ?? $this->database());
    }

    /**
     * Prepare an action's parameters to fulfill its required/non-required parameters needed on
     * calltime/runtime.
     *
     * @param  ReflectionMethod|ReflectionFunction $ref
     * @param  array                               $actionParams
     * @return array
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
