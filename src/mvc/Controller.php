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

namespace froq\mvc;

use froq\{App, Router};
use froq\mvc\{ControllerException, View, Model, Action};
use Reflector, ReflectionMethod, ReflectionFunction;

/**
 * Controller.
 *
 * Represents a controller entity which is a part of MVC pattern.
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
    public const NAMESPACE     = 'app\controller';

    /**
     * Default.
     * @const string
     */
    public const DEFAULT       = 'app\controller\IndexController';

    /**
     * Name defaults.
     * @const string
     */
    public const NAME_DEFAULT  = '@default',
                 NAME_CLOSURE  = '@closure';

    /**
     * Suffixes.
     * @const string
     */
    public const SUFFIX        = 'Controller',
                 ACTION_SUFFIX = 'Action';

    /**
     * Default actions.
     * @const string
     */
    public const INDEX_ACTION  = 'index',
                 ERROR_ACTION  = 'error';

    /**
     * App.
     * @var froq\App
     */
    protected App $app;

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
    private array  $actionParams;

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
     * Constructor.
     *
     * Calls `loadView()` method if `$useView` set to true.
     * Calls `loadModel()` method if `$useModel` set to true.
     * Calls `init()` method if defined in subclass.
     *
     * @param froq\App $app
     */
    public final function __construct(App $app)
    {
        $this->app = $app;

        $this->useView && $this->loadView();
        $this->useModel && $this->loadModel();
        $this->useSession && $this->loadSession();

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * Gets the app property.
     *
     * @return froq\App
     */
    public final function getApp(): App
    {
        return $this->app;
    }

    /**
     * Gets the name of controller that run at the time, creating if not set yet.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this->name ?? (
               $this->name = substr(strrchr(static::class, '\\'), 1)
        );
    }

    /**
     * Gets the short name of controller that run at the time.
     *
     * @return string
     */
    public final function getShortName(): string
    {
        $name = $this->getName();

        if (strpos($name, self::SUFFIX)) {
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

        if (strpos($action, self::ACTION_SUFFIX)) {
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
        if (empty($this->view)) {
            $layout = $this->app->config('view.layout');
            if (!$layout) {
                throw new ControllerException('No "view.layout" option found in configuration');
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
        if (empty($this->model)) {
            $name  = $this->getShortName();
            $class = sprintf('app\model\%sModel', $name);
            if (!class_exists($class)) {
                throw new ControllerException( 'Model class "%s" not found, be sure file '.
                    '"app/system/%s/model/%sModel.php" exists', [$class, $name, $name]);
            }

            $this->model = new $class($this);
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
        if ($session == null) {
            throw new ControllerException('App has no session object (check "session" option in '.
                'configuration and be sure it is not null)');
        }

        $session->start();
    }

    /**
     * Env.
     * @param  string   $name
     * @param  any|null $valueDefault
     * @return any
     */
    public final function env(string $name, $valueDefault = null)
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

        return $value ?? $valueDefault;
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
     * Views a view file with given `$meta` and `$data` arguments if provided rendering the file
     * in a wrapped output buffer.
     *
     * @param  string     $file
     * @param  array|null $meta
     * @param  array|null $data
     * @param  int|null   $status
     * @return string
     * @throws froq\mvc\ControllerException
     */
    public final function view(string $file, array $meta = null, array $data = null, int $status = null): string
    {
        if (!isset($this->view)) {
            throw new ControllerException('No "$view" property set yet, be sure "$useView" is '.
                'true in %s class', [static::class]);
        }

        if ($status) {
            $this->app->response()->status($status);
        }

        return $this->view->render($file, ['meta' => $meta, 'data' => $data]);
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
        @ [$controller, $action, $actionParams] = Router::prepare($call, $callArgs);

        if ($controller == null || $action == null) {
            throw new ControllerException('Invalid call directive given, use "Foo.bar" '.
                'convention without "Controller" and "Action" suffixes', null, 404);
        } elseif (!class_exists($controller)) {
            throw new ControllerException('No controller found such "%s"', [$controller], 404);
        } elseif (!method_exists($controller, $action)) {
            throw new ControllerException('No controller action found such "%s::%s()"', [$controller, $action], 404);
        }

        return (new $controller($this->app))->call($action, $actionParams);
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
    public final function redirect(string $to, array $toArgs = null, int $code = 302,
        array $headers = null, array $cookies = null): void
    {
        if ($toArgs) $to = vsprintf($to, $toArgs);

        $this->app->response()->redirect($to, $code, $headers, $cookies);
    }

    /**
     * Sets response status & body content, also content attributes if provided.
     *
     * @param  int        $code
     * @param  any        $content
     * @param  array|null $contentAttributes
     * @return void
     */
    public final function response(int $code, $content, array $contentAttributes = null): void
    {
        $this->app->response()->setStatus($code)->setBody($content, $contentAttributes);
    }

    /**
     * Sets response (status) code.
     *
     * @param  int $code
     * @return self
     */
    public final function setResponseCode(int $code): self
    {
        $this->app->response()->setStatus($code);

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
        $this->app->response()->setContentType($type);

        return $this;
    }

    /**
     * Gets a get parameter.
     *
     * @param  string   $name
     * @param  any|null $valueDefault
     * @return any|null
     */
    public final function getParam(string $name, $valueDefault = null)
    {
        return $this->app->response()->getParam($name, $valueDefault);
    }

    /**
     * Gets all get parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $valuesDefault
     * @return array
     */
    public final function getParams(array $names = null, $valuesDefault = null): array
    {
        return $this->app->response()->getParams($names, $valuesDefault);
    }

    /**
     * Gets a post parameter.
     *
     * @param  string   $name
     * @param  any|null $valueDefault
     * @return any|null
     */
    public final function postParam(string $name, $valueDefault = null)
    {
        return $this->app->response()->postParam($name, $valueDefault);
    }

    /**
     * Gets all post parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $valuesDefault
     * @return array
     */
    public final function postParams(array $names = null, $valuesDefault = null): array
    {
        return $this->app->response()->postParams($names, $valuesDefault);
    }

    /**
     * Gets a cookie parameter.
     *
     * @param  string   $name
     * @param  any|null $valueDefault
     * @return any|null
     */
    public final function cookieParam(string $name, $valueDefault = null)
    {
        return $this->app->response()->cookieParam($name, $valueDefault);
    }

    /**
     * Gets all cookie parameters or given names only.
     *
     * @param  array<string>|null $names
     * @param  any|null           $valuesDefault
     * @return array
     */
    public final function cookieParams(array $names = null, $valuesDefault = null): array
    {
        return $this->app->response()->cookieParams($names, $valuesDefault);
    }

    /**
     * Calls an action that defined in subclass or by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", "Book.show")`.
     *
     * @param  string $action
     * @param  array  $actionParams
     * @return any
     */
    public final function call(string $action, array $actionParams = [])
    {
        $this->action       = $action;
        $this->actionParams = $actionParams; // Keep originals.

        $params = $this->prepareActionParams(new ReflectionMethod($this, $action), $actionParams);

        return $this->{$action}(...$params);
    }

    /**
     * Calls an callable (function) that defined by `App.route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", function ($id) { .. })`.
     *
     * @param  callable $action
     * @param  array    $actionParams
     * @return any
     */
    public final function callCallable(callable $action, array $actionParams = [])
    {
        $this->action       = self::NAME_CLOSURE;
        $this->actionParams = $actionParams; // Keep originals.

        // Make "$this" available in called action.
        $action = $action->bindTo($this, $this);

        $params = $this->prepareActionParams(new ReflectionFunction($action), $actionParams);

        return $action(...$params);
    }

    /**
     * Prepares an action's parameters to fulfill its required/non-required parameter need on
     * calltime/runtime.
     *
     * @param  Reflector $reflector
     * @param  array     $actionParams
     * @return array
     */
    private final function prepareActionParams(Reflector $reflector, array $actionParams): array
    {
        $ret = [];

        foreach ($reflector->getParameters() as $i => $param) {
            // Action parameter can be named or indexed.
            $ret[] = $actionParams[$param->name] ?? $actionParams[$i] ?? (
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }

        return $ret;
    }
}
