<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ModelTrait};
use froq\database\{Query, sql\Sql};
use froq\database\trait\{DbTrait, TableTrait, ValidationTrait};

/**
 * Repository
 *
 * Represents an entity which is extended by producers/providers or other data/database related classes.
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
    use DbTrait, TableTrait, ValidationTrait;

    /**
     * Constructor.
     *
     * @param froq\mvc\Controller|null $controller
     */
    public function __construct(Controller $controller = null)
    {
        // Use given or registry controller.
        $this->controller = $controller ?? registry()::get('@controller');

        // Not all controllers use models.
        if ($model = $this->controller->getModel()) {
            $this->model = $model;
        }

        // Set db property using model or registry.
        $this->db = $model?->db() ?: registry()::get('@app')->database();
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
