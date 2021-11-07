<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\data\DataException;
use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ModelTrait};
use froq\database\{Database, Repository as _Repository};

/**
 * Repository.
 *
 * Represents an entity which is extended by producers/providers or other database related classes.
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\Repository
 * @author  Kerem Güneş
 * @since   5.0
 */
class Repository extends _Repository
{
    /** @see froq\mvc\trait\ControllerTrait */
    /** @see froq\mvc\trait\ModelTrait */
    use ControllerTrait, ModelTrait;

    /**
     * Constructor.
     *
     * @param  froq\mvc\Controller|null    $controller
     * @param  froq\database\Database|null $db
     * @throws froq\mvc\DataException
     */
    public function __construct(Controller $controller = null, Database $db = null)
    {
        // Use given or registry's controller.
        $controller ??= registry()::get('@controller');
        if ($controller != null) {
            $this->controller = $controller;

            // Not all controllers use models.
            if ($model = $controller->getModel()) {
                $this->model = $model;

                // Use model's stuff.
                if ($db == null) {
                    $db = $model->db();
                    $em = $model->em();
                }
            }
        }

        // Try to use active app database object.
        $db ??= registry()::get('@app')->database() ?: throw new DataException(
            'No db exists to deal, check `database` option in app config or pass $db argument'
        );

        parent::__construct($db, $em ?? null);
    }
}
