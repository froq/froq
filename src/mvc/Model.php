<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ModelException, Controller};
use froq\database\{Database, Result, Query};
use froq\database\trait\{DbTrait, TableTrait, ValidationTrait};
use froq\{pager\Pager, common\object\Registry};

/**
 * Model.
 *
 * Represents a model entity which is a part of MVC stack.
 *
 * @package froq\mvc
 * @object  froq\mvc\Model
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class Model
{
    /**
     * @see froq\database\trait\DbTrait
     * @see froq\database\trait\TableTrait
     * @see froq\database\record\ValidationTrait
     * @since 5.0
     */
    use DbTrait, TableTrait, ValidationTrait;

    /**
     * Namespace.
     * @const string
     */
    public const NAMESPACE = 'app\model';

    /**
     * Suffix.
     * @const string
     */
    public const SUFFIX = 'Model';

    /**
     * Controller.
     * @var froq\mvc\Controller
     */
    protected Controller $controller;

    /**
     * Constructor.
     *
     * @param  froq\mvc\Controller         $controller
     * @param  froq\database\Database|null $database
     * @throws froq\mvc\ModelException
     */
    public final function __construct(Controller $controller, Database $database = null)
    {
        $this->controller = $controller;

        $db = $database ?? $controller->getApp()->database();
        if ($db == null) {
            throw new ModelException('No database given to deal, be sure `database` option exists in'
                . ' app config');
        }

        $this->db = $db;

        // When defined on child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store (as last) model.
        Registry::set('@model', $this, false);
    }

    /**
     * Gets the controller property.
     *
     * @return froq\mvc\Controller
     */
    public final function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * Alias of initQuery().
     *
     * @since 5.0
     */
    public final function query(...$args)
    {
        return $this->initQuery(...$args);
    }

    /**
     * Validate given data by key given rules or self rules, modifying given `$data` and filling `$errors`
     * if validation fails.
     *
     * @param  array       &$data
     * @param  array|null   $rules
     * @param  array|null  &$errors
     * @param  array|null   $options
     * @return bool
     * @since  4.8
     */
    public final function validate(array &$data, array $rules = null, array &$errors = null, array $options = null): bool
    {
        // Validation rules & options can be also defined in child models.
        $rules ??= $this->getValidationRules();
        $options ??= $this->getValidationOptions();

        if (empty($data)) {
            throw new ModelException('Empty data given for validation');
        }
        if (empty($rules)) {
            throw new ModelException('No validation rules set yet, call setValidationRules() or pass $rules argument'
                . ' or define $validationRules property on %s class', static::class);
        }

        return $this->runValidation($data, $rules, $options, $errors);
    }

    /**
     * Initializes a model object by given model/model class name.
     *
     * @param  string                   $class
     * @param  froq\mvc\Controller|null $controller
     * @return froq\mvc\Model (static)
     */
    public final function initModel(string $class, Controller $controller = null): Model
    {
        return $this->controller->initModel($class, $controller, $this->db);
    }

    /**
     * Initialize a new pager object running it with `$count` and `$limit` arguments if provided.
     *
     * @param  int|null $count
     * @param  int|null $limit
     * @return froq\pager\Pager
     */
    public final function initPager(int $count = null, int $limit = null): Pager
    {
        return $this->db->initPager($count, $limit);
    }

    /**
     * Initializes a new query object using self `$db` property, setting its "table" query
     * with `$table` argument if provided.
     *
     * @param  string|null $table
     * @return froq\database\Query
     */
    public final function initQuery(string $table = null): Query
    {
        return $this->db->initQuery($table ?? $this->table ?? null);
    }
}
