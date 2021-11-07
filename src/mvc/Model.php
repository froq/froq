<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ModelException, Controller};
use froq\mvc\data\{Producer, Provider, Repository};
use froq\mvc\trait\ControllerTrait;
use froq\database\{Database, Query, sql\Sql, entity\Manager};
use froq\database\trait\{DbTrait, TableTrait, ValidationTrait, EntityManagerTrait};
use froq\database\record\{Form, Record};
use froq\pager\Pager;

/**
 * Model.
 *
 * Represents a model entity which is a part of MVC stack.
 *
 * @package froq\mvc
 * @object  froq\mvc\Model
 * @author  Kerem Güneş
 * @since   4.0
 */
class Model
{
    /** @see froq\mvc\trait\ControllerTrait */
    use ControllerTrait;

    /**
     * @see froq\database\trait\DbTrait
     * @see froq\database\trait\TableTrait
     * @see froq\database\trait\ValidationTrait
     * @see froq\database\trait\EntityManagerTrait
     * @since 5.0
     */
    use DbTrait, TableTrait, ValidationTrait, EntityManagerTrait;

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
        $db ??= $controller->app()->database();
        $db || throw new ModelException(
            'No db exists to deal, check `database` option in app config or pass $db argument'
        );

        $this->controller = $controller;
        $this->db         = $db;
        $this->em         = new Manager($db);

        // When defined on child class.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store (as last) model.
        $controller->app()::registry()::set('@model', $this, false);
    }

    /**
     * @alias of initQuery()
     * @since 5.0
     */
    public final function sql(...$args)
    {
        return $this->initSql(...$args);
    }

    /**
     * @alias of initQuery()
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
            throw new ModelException('No validation rules set yet, call setValidationRules() or pass $rules'
                . ' argument or define $validationRules property on %s class', static::class);
        }

        return $this->runValidation($data, $rules, $options, $errors);
    }

    /**
     * Initialize a model object by given model/model class name.
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
     * Init a `Sql` object with/without given params argument.
     *
     * @param  string     $in
     * @param  array|null $params
     * @return froq\database\sql\Sql
     */
    public final function initSql(string $in, array $params = null): Sql
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
    public final function initQuery(string $table = null): Query
    {
        return $this->db->initQuery($table ?? $this->table ?? null);
    }

    /**
     * Other initializers.
     *
     * @param  string|null $class
     * @param  array|null  $classArgs
     * @return object
     * @causes froq\mvc\ModelException
     * @since  5.0
     */
    public final function initForm(string $class = null, array $classArgs = null): Form
    {
        return $this->initFor(__function__, $class, $classArgs);
    }
    public final function initRecord(string $class = null, array $classArgs = null): Record
    {
        return $this->initFor(__function__, $class, $classArgs);
    }
    public final function initProducer(string $class = null, array $classArgs = null): Producer
    {
        return $this->initFor(__function__, $class, $classArgs);
    }
    public final function initProvider(string $class = null, array $classArgs = null): Provider
    {
        return $this->initFor(__function__, $class, $classArgs);
    }
    public final function initRepository(string $class = null, array $classArgs = null): Repository
    {
        return $this->initFor(__function__, $class, $classArgs);
    }

    // /**
    //  * @alias of other initializers.
    //  * @since 5.0
    //  */
    // public final function getForm(...$args) { return $this->initForm(...$args); }
    // public final function getRecord(...$args) { return $this->initRecord(...$args); }
    // public final function getProducer(...$args) { return $this->initProducer(...$args); }
    // public final function getProvider(...$args) { return $this->initProvider(...$args); }
    // public final function getRepository(...$args) { return $this->initRepository(...$args); }

    /**
     * Internal initializer.
     *
     * @param  string $func
     * @param  null   $class
     * @param  null   $classArgs
     * @return object
     * @throws froq\mvc\ModelException
     * @since  5.0
     */
    private function initFor(string $func, string|null $class, array|null $classArgs): object
    {
        $suffix = substr($func, 4);
        $subdir = strtolower($suffix);

        // When a sub-model's record, repository etc. wanted.
        if ($class == null) {
            $parts = explode('\\', static::class);

            $class = array_pop($parts);
            $class = implode('\\', [...$parts, $subdir, ''])
                   . substr($class, 0, -strlen(self::SUFFIX))
                   . $suffix;
        } else {
            // Dots can be used instead back-slashes.
            $class = trim(str_replace('.', '\\', $class), '\\');

            // When only name given (with/without eg. "Record" suffix).
            if (!strpos($class, '\\')) {
                $class = implode('\\', [self::NAMESPACE, $subdir, ''])
                       . ucfirst($class)
                       . $suffix;
            }
        }

        if (class_exists($class)) {
            return new $class(...(array) $classArgs);
        }

        throw new ModelException('%s class `%s` not exists', [$suffix, $class]);
    }
}
