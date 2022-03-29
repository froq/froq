<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\trait\ControllerTrait;
use froq\database\{Database, Query, sql\Sql, entity\Manager as EntityManager};
use froq\database\trait\{DbTrait, EmTrait, TableTrait, ValidationTrait};
use froq\pager\Pager;

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
        // Use given or app's database.
        $db ??= $controller->app()->database() ?: throw new ModelException(
            'No db exists to deal, check `database` option in app config or pass $db argument'
        );

        $this->controller = $controller;
        $this->db         = $db;
        $this->em         = new EntityManager($db);

        // When defined on child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store this model (as last model).
        $controller->app()::registry()::set('@model', $this, false);
    }

    /**
     * Validate given data by key given rules or self rules, modifying given `$data` and filling `$errors`
     * if validation fails.
     *
     * @param  array       &$data
     * @param  array|null   $rules
     * @param  array|null  &$errors
     * @param  array|null   $options
     * @throws froq\mvc\ModelException
     * @return bool
     * @since  4.8
     */
    public final function validate(array &$data, array $rules = null, array &$errors = null, array $options = null): bool
    {
        // Validation rules & options can be also defined in child models.
        $rules   ??= $this->getValidationRules();
        $options ??= $this->getValidationOptions();

        if (empty($data)) {
            throw new ModelException('Empty data given for validation');
        }
        if (empty($rules)) {
            throw new ModelException('No validation rules set yet, call setValidationRules() or pass $rules '.
                'argument or define $validationRules property on %s class', static::class);
        }

        return $this->runValidation($data, $rules, $options, $errors);
    }

    /**
     * Initialize a model object by given model/model class name.
     *
     * @param  string                   $class
     * @param  froq\mvc\Controller|null $controller
     * @return froq\mvc\Model
     */
    public final function initModel(string $class, Controller $controller = null): Model
    {
        return $this->controller->initModel($class, $controller, $this->db);
    }

    /**
     * Initialize a new pager object running it with `$count` and `$limit` arguments if provided.
     *
     * @param  int|null   $count
     * @param  array|null $attributes
     * @return froq\pager\Pager
     */
    public final function initPager(int $count = null, array $attributes = null): Pager
    {
        return $this->db->initPager($count, $attributes);
    }

    /**
     * Init a `Sql` object with/without given params argument.
     *
     * @param  string     $input
     * @param  array|null $params
     * @return froq\database\sql\Sql
     */
    public final function initSql(string $input, array $params = null): Sql
    {
        return $this->db->initSql($input, $params);
    }

    /**
     * Init a `Query` object using self `$db` property, setting its "table" query with `$table` argument
     * when provided or using self `$table` property.
     *
     * @param  string|null $table
     * @return froq\database\Query
     */
    public final function initQuery(string $table = null): Query
    {
        return $this->db->initQuery($table ?? $this->table ?? null);
    }
}
