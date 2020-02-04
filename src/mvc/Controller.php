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
    public const NAMESPACE = 'app\controller';

    /**
     * Default.
     * @const string
     */
    public const DEFAULT = 'app\controller\IndexController';

    /**
     * Name defaults.
     * @const string
     */
    public const NAME_DEFAULT = '@default',
                 NAME_CLOSURE = '@closure';

    /**
     * Suffixes.
     * @const string
     */
    public const SUFFIX = 'Controller',
                 ACTION_SUFFIX = 'Action';

    /**
     * Default actions.
     * @const string
     */
    public const INDEX_ACTION = 'index',
                 ERROR_ACTION = 'error';


    /**
     * App.
     * @var froq\App
     */
    private App $app;

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
     * property set to true. Throws a `ControllerException` if no `viewLayout` option found in
     * configuration.
     *
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function loadView(): void
    {
        if (empty($this->view)) {
            $layout = $this->app->configuration()['viewLayout'] ?? null;
            if (!$layout) {
                throw new ControllerException('No "viewLayout" option found in configuration');
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
            $name      = $this->getName();
            $shortName = $this->getShortName();

            $class = sprintf('app\model\%sModel', $shortName);
            if (!class_exists($class)) {
                throw new ControllerException(
                    'Model class "%s" not found, be sure "app/system/%s/model/%sModel.php" exists',
                    [$class, $name, $shortName]
                );
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
     * Views (prints) a view file with given `$meta` and `$data` arguments if provided rendering
     * the file in a wrapped output buffer.
     *
     * @param  string     $file
     * @param  array|null $meta
     * @param  array|null $data
     * @return void
     */
    public final function view(string $file, array $meta = null, array $data = null): void
    {
        $this->view->render($file, ['meta' => $meta, 'data' => $data]);
    }

    /**
     * Forwards an internal call to other call (controller method) with given call arguments. The
     * `$call` parameter must be fully qualified for explicit methods without `Controller` and
     * `Action` suffixes eg: "Book.show", otherwise `index` method does not require that explicity.
     *
     * @param  string $call
     * @return void
     * @throws froq\mvc\ControllerException
     */
    public final function forward(string $call, array $callArgs = [])
    {
        @ [$controller, $action, $actionParams] = Router::prepare($call, $callArgs);

        if (!$controller) {
            throw new ControllerException('Invalid call directive given, use "Foo.bar" convention without '.
                '"Controller" and "Action" suffixes', [], 404);
        } elseif (!class_exists($controller)) {
            throw new ControllerException('No controller found such "%s"', [$controller], 404);
        } elseif (!method_exists($controller, $action)) {
            throw new ControllerException('No controller action found such "%s::%s"', [$controller, $action], 404);
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
        $toArgs && $to = vsprintf($to, $toArgs);

        $this->app->response()->redirect($to, $code, $headers, $cookies);
    }

    /**
     * Calls an action that defined in subclass or by App's `route()` method or other shortcut
     * route methods like `get()`, `post()`, eg: `$app->get("/book/:id", "Book.show")`..
     *
     * @param  string $action
     * @param  array  $actionParams
     * @return any
     */
    public final function call(string $action, array $actionParams = [])
    {
        $this->action       = $action;
        $this->actionParams = $actionParams;

        return Action::call($this, $action, $actionParams);
    }

    /**
     * Calls an callable (function) that defined by App's `route()` method or other shortcut
     * route methods like `get()`, `post()`, eg: `$app->get("/book/:id", function ($id) { .. })`.
     *
     * @param  callable $action
     * @param  array    $actionParams
     * @return any
     */
    public final function callCallable(callable $action, array $actionParams = [])
    {
        $this->action       = self::NAME_CLOSURE;
        $this->actionParams = $actionParams;

        // Make "$this" and "$this->..." available in called action.
        $action = $action->bindTo($this, $this);

        return Action::callCallable($action, $actionParams);
    }
}
