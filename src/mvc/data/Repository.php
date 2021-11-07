<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\data\RepositoryException;
use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ModelTrait};
use froq\database\{Query, sql\Sql, entity\Manager};
use froq\database\trait\{DbTrait, TableTrait, ValidationTrait, EntityManagerTrait};

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
class Repository
{
    /** @see froq\mvc\trait\ControllerTrait */
    /** @see froq\mvc\trait\ModelTrait */
    use ControllerTrait, ModelTrait;

    /** @see froq\database\trait\DbTrait */
    /** @see froq\database\trait\TableTrait */
    /** @see froq\database\trait\ValidationTrait */
    /** @see froq\database\trait\EntityManagerTrait */
    use DbTrait, TableTrait, ValidationTrait, EntityManagerTrait;

    /**
     * Constructor.
     *
     * @param  froq\mvc\Controller|null    $controller
     * @param  froq\database\Database|null $db
     * @throws froq\mvc\ModelException
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
        $db ??= registry()::get('@app')->database() ?: throw new RepositoryException(
            'No db exists to deal, check `database` option in app config or pass $db argument'
        );

        $this->db = $db;
        $this->em = $em ?? new Manager($db);
    }

    /**
     * Init a `Sql` object with/without given params argument.
     *
     * @param  string     $in
     * @param  array|null $params
     * @return froq\database\sql\Sql
     */
    public final function sql(string $in, array $params = null): Sql
    {
        return $this->db->initSql($in, $params);
    }

    /**
     * Init a `Query` object using self `$db` property, setting its "table" query with `$table` argument
     * when provided or using self `$table` property.
     *
     * @param  string|null $table
     * @return froq\database\Query
     */
    public final function query(string $table = null): Query
    {
        return $this->db->initQuery($table ?? $this->table ?? null);
    }
}
