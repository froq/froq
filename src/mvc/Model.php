<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\trait\ControllerTrait;
use froq\database\{Database, entity\Manager as EntityManager};
use froq\database\trait\{DbTrait, EmTrait, TableTrait, ValidationTrait};

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
}
