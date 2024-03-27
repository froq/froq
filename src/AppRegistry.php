<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

use froq\common\object\Registry;
use froq\app\{Controller, Repository, View};

/**
 * A registry class for app, controller, repository and view objects.
 *
 * @package froq
 * @class   froq\AppRegistry
 * @author  Kerem Güneş
 * @since   7.0
 */
class AppRegistry extends Registry
{
    /** Registry ids. */
    private const APP_ID        = '@app',
                  CONTROLLER_ID = '@controller',
                  REPOSITORY_ID = '@repository',
                  VIEW_ID       = '@view';

    /**
     * Set app.
     *
     * @param  froq\App $app
     * @param  bool     $locked
     * @return void
     */
    public static function setApp(App $app, bool $locked = false): void
    {
        self::set(self::APP_ID, $app, $locked);
    }

    /**
     * Get app.
     *
     * @return froq\App|null
     */
    public static function getApp(): App|null
    {
        return self::get(self::APP_ID);
    }

    /**
     * Set controller.
     *
     * @param  froq\app\Controller $controller
     * @param  bool                $locked
     * @return void
     */
    public static function setController(Controller $controller, bool $locked = false): void
    {
        self::set(self::CONTROLLER_ID, $controller, $locked);
    }

    /**
     * Get controller.
     *
     * @return froq\app\Controller|null
     */
    public static function getController(): Controller|null
    {
        return self::get(self::CONTROLLER_ID);
    }

    /**
     * Set repository.
     *
     * @param  froq\app\Repository $repository
     * @param  bool                $locked
     * @return void
     */
    public static function setRepository(Repository $repository, bool $locked = false): void
    {
        self::set(self::REPOSITORY_ID, $repository, $locked);
    }

    /**
     * Get repository.
     *
     * @return froq\app\Repository|null
     */
    public static function getRepository(): Repository|null
    {
        return self::get(self::REPOSITORY_ID);
    }

    /**
     * Set view.
     *
     * @param  froq\app\View $view
     * @param  bool          $locked
     * @return void
     */
    public static function setView(View $view, bool $locked = false): void
    {
        self::set(self::VIEW_ID, $view, $locked);
    }

    /**
     * Get view.
     *
     * @return froq\app\View|null
     */
    public static function getView(): View|null
    {
        return self::get(self::VIEW_ID);
    }
}
