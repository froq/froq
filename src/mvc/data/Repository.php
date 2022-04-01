<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ModelTrait};
use froq\database\{Database, DatabaseException, common\Helper, Repository as BaseRepository};

/**
 * Repository.
 *
 * A class, contains most required data read/write tools and intended to use other
 * repository classes and producers/providers or other database related classes in
 * a MVC environment.
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\Repository
 * @author  Kerem Güneş
 * @since   5.0
 */
class Repository extends BaseRepository
{
    use ControllerTrait, ModelTrait;

    /**
     * Constructor.
     *
     * @param  froq\mvc\Controller|null    $controller
     * @param  froq\database\Database|null $db
     * @throws froq\mvc\data\DataException
     */
    public function __construct(Controller $controller = null, Database $db = null)
    {
        // Use given or registry's controller.
        $controller ??= registry()::get('@controller');

        if ($controller) {
            $this->controller = $controller;

            // Not all controllers use models.
            if ($model = $controller->getModel()) {
                $this->model = $model;

                // Use model's stuff.
                if (!$db) {
                    $db = $model->db();
                    $em = $model->em();
                }
            }
        }

        if (!$db) try {
            $db = Helper::getActiveDatabase();
        } catch (DatabaseException $e) {
            throw new DataException($e->message);
        }

        parent::__construct($db, $em ?? null);
    }
}
