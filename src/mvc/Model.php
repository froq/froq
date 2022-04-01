<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\trait\ControllerTrait;
use froq\database\trait\{DbTrait, EmTrait, TableTrait, ValidationTrait};
use froq\database\{Database, DatabaseException, common\Helper, entity\Manager as EntityManager};

/**
 * Model.
 *
 * A class, part of MVC stack and extended by other `app\model` classes.
 *
 * @package froq\mvc
 * @object  froq\mvc\Model
 * @author  Kerem Güneş
 * @since   4.0
 */
class Model
{
    use ControllerTrait, DbTrait, EmTrait, TableTrait, ValidationTrait;

    /** @const string */
    public const NAMESPACE = 'app\model';

    /** @const string */
    public const SUFFIX    = 'Model';

    /**
     * Constructor.
     *
     * @param  froq\mvc\Controller         $controller
     * @param  froq\database\Database|null $db
     * @throws froq\mvc\ModelException
     */
    public final function __construct(Controller $controller, Database $db = null)
    {
        $this->controller = $controller;

        if (!$db) try {
            $db = Helper::getActiveDatabase();
        } catch (DatabaseException $e) {
            throw new ModelException($e->message);
        }

        $this->db = $db;
        $this->em = new EntityManager($db);

        // When defined on child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store this model (as last model).
        $controller->app()::registry()::set('@model', $this, false);
    }
}
