<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\common\objects\Registry;
use froq\database\{Database, Result, Query};
use froq\{pager\Pager, validation\Validation};
use froq\mvc\{ModelException, Controller};

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
     * Namespace.
     * @const string
     */
    public const NAMESPACE  = 'app\model';

    /**
     * Suffix.
     * @const string
     */
    public const SUFFIX     = 'Model';

    /**
     * Controller.
     * @var froq\mvc\Controller
     */
    protected Controller $controller;

    /**
     * Db.
     * @var froq\database\Database
     */
    protected Database $db;

    /**
     * Table.
     * @var string
     */
    protected string $table;

    /**
     * Table primary.
     * @var string
     */
    protected string $tablePrimary;

    /**
     * Validation rules.
     * @var array
     * @since 4.9
     */
    protected array $validationRules;

    /**
     * Validation options.
     * @var array
     * @since 4.9
     */
    protected array $validationOptions;

    /**
     * Constructor.
     *
     * Creates a new `Model` and calls `init()` method if defined in subclass.
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
            throw new ModelException('No database given to deal, be sure "database" option '.
                'exists in app configuration');
        }

        $this->db = $db;

        if (method_exists($this, 'init')) {
            $this->init();
        }

        // @todo: For 5.0 version.
        // // If one of these provided in child model, then will be used by Recorder object.
        // $this->recorder = new Recorder($db, [
        //     'table'             => $this->table ?? null,
        //     'tablePrimary'      => $this->tablePrimary ?? null,
        //     'validationRules'   => $this->validationRules ?? null,
        //     'validationOptions' => $this->validationOptions ?? null,
        // ]);

        // Store (last) model.
        Registry::set('@model', $this, true);
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
     * Gets the db property.
     *
     * @return froq\database\Database
     */
    public final function db(): Database
    {
        return $this->db;
    }

    /**
     * Gets the table property if set in subclass, otherwise returns null.
     *
     * @return ?string
     */
    public final function table(): ?string
    {
        return $this->table ?? null;
    }

    /**
     * Gets the table primary property if set in subclass, otherwise returns null.
     *
     * @return ?string
     */
    public final function tablePrimary(): ?string
    {
        return $this->tablePrimary ?? null;
    }

    /**
     * Validates given data by key given rules, also modifies given `$data` and fills `$fails`
     * if validation not passes.
     *
     * @param  array       &$data
     * @param  array        $rules
     * @param  array|null  &$fails
     * @param  array|null   $options
     * @return bool
     * @since  4.8
     */
    public final function validate(array &$data, array $rules, array &$fails = null, array $options = null): bool
    {
        // Validation rules & options can be also defined in child models.
        $rules ??= $this->validationRules ?? null;
        $options ??= $this->validationOptions ?? null;

        $validation = new Validation($rules, $options);

        return $validation->validate($data, $fails);
    }

    /**
     * Load validations rules from given file.
     *
     * @param  string|null $file
     * @param  string      $key
     * @return array
     * @throws froq\mvc\ModelException
     * @since  4.15
     */
    public final function loadValidations(string $file = null): array
    {
        // Try to load default file from config directory (or directory, eg: config/user/add).
        $file = APP_DIR .'/app/config/'. ($file ?: 'validations') .'.php';

        if (!is_file($file)) {
            throw new ModelException('No validations file "%s" exists', [$file]);
        }

        return include $file;
    }

    /**
     * Load validations rules (from given file if provided) with given key.
     *
     * @param  string|null $file
     * @param  string      $key
     * @return array
     * @throws froq\mvc\ModelException
     * @since  4.15
     */
    public final function loadValidationRules(string $file = null, string $key): array
    {
        $validations = $this->loadValidations($file);

        if (empty($validations[$key])) {
            throw new ModelException('No rules found for "%s" key', [$key]);
        }

        return $validations[$key];
    }

    /**
     * Initializes a model object by given model/model class name.
     *
     * @param  string                   $name
     * @param  froq\mvc\Controller|null $controller
     * @return froq\mvc\Model (static)
     */
    public final function initModel(string $name, Controller $controller = null): Model
    {
        return $this->controller->initModel($name, $controller, $this->db);
    }

    /**
     * Initializes a new pager object running it with `$totalRecords` argument if provided.
     *
     * @param  int|null $totalRecords
     * @param  int|null $limit
     * @return froq\pager\Pager
     */
    public final function initPager(int $totalRecords = null, int $limit = null): Pager
    {
        return $this->db->initPager($totalRecords, $limit);
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
