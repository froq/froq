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

use froq\pager\Pager;
use froq\mvc\{ModelException, Controller};
use froq\database\{Database, Result, QueryBuilder};

/**
 * Model.
 *
 * Represents a model entity which is a part of MVC pattern.
 *
 * @package froq\mvc
 * @object  froq\mvc\Model
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
class Model
{
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
     * Data.
     * @var array
     */
    private array $data;

    /**
     * Constructor.
     *
     * Creates a new `Database` object if not already given and calls `init()` method if
     * defined in subclass.
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
            throw new ModelException('No database given to deal, be sure "database" option is '.
                'exists in configuration');
        }

        $this->db = $db;

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * Gets the controller property.
     *
     * @return froq\mvc\Controller
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * Gets the db property.
     *
     * @return froq\database\Database
     */
    public final function getDb(): Database
    {
        return $this->db;
    }

    /**
     * Gets the table property if set in subclass, otherwise returns null.
     *
     * @return ?string
     */
    public final function getTable(): ?string
    {
        return $this->table ?? null;
    }

    /**
     * Gets the table primary property if set in subclass, otherwise returns null.
     *
     * @return ?string
     */
    public final function getTablePrimary(): ?string
    {
        return $this->tablePrimary ?? null;
    }

    /**
     * Sets a data value with given key.
     *
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    public final function set(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Gets a data value with given key if set in data, otherwise returns `$valueDefault` value.
     *
     * @param string $key
     * @param any    $value
     */
    public final function get(string $key, $valueDefault = null)
    {
        return $this->data[$key] ?? $valueDefault;
    }

    /**
     * Checks a data value is set or not.
     *
     * @param  string $key
     * @return bool
     */
    public final function isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Removes a data value with given key from data array.
     *
     * @param  string $key
     * @return void
     */
    public final function unset(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Sets the data property.
     *
     * @param  array $data
     * @return void
     */
    public final function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Gets the data property if set, otherwise returns null.
     *
     * @return array<string, any>
     */
    public final function getData(): ?array
    {
        return $this->data ?? null;
    }

    /**
     * Runs a query command.
     *
     * @param  string     $query
     * @param  array|null $queryParams
     * @param  array|null $fetchOptions
     * @return froq\database\Result
     */
    public function query(string $query, array $queryParams = null, array $fetchOptions = null): Result
    {
        return $this->db->query($query, $queryParams, $fetchOptions);
    }

    /**
     * Finds a row entry by given primary value if exists, otherwise returns null.
     *
     * @param  int        $id
     * @param  array|null $fetchOptions
     * @return ?array|?object
     */
    public function find(int $id, array $fetchOptions = null)
    {
        [$table, $tablePrimary] = $this->packTableStuff(__method__);

        return $this->initQueryBuilder($table)->select('*')
                    ->whereEqual($tablePrimary, [$id])
                    ->get($fetchOptions);
    }

    /**
     * Removes a row entry by given primary value and returns affected rows count.
     *
     * @param  int $id
     * @return int
     */
    public function remove(int $id): int
    {
        [$table, $tablePrimary] = $this->packTableStuff(__method__);

        return $this->db->transaction(function () use ($table, $tablePrimary, $id) {
            return $this->initQueryBuilder($table)->delete()
                        ->whereEqual($tablePrimary, [$id])
                        ->run()->count();
        });
    }

    /**
     * Save a row entry with given data set. If primary provided in data set, returns new primary
     * value updating current row, otherwise returns affected rows count inserting new data set.
     * Throws a `ModelException` if empty data set given.
     *
     * @param  array|null $data
     * @return int
     */
    public function save(array $data = null): int
    {
        [$table, $tablePrimary] = $this->packTableStuff(__method__);

        $data = $data ?? $this->getData();
        if (!$data) {
            throw new ModelException('Non-empty data required to use "%s()"', [__method__]);
        }

        // Get id if exists.
        $id = $data[$tablePrimary] ?? null;

        if (is_null($id)) {
            // Insert action.
            return $this->db->transaction(function () use ($data, $table, $tablePrimary) {
                $id = $this->initQueryBuilder($table)->insert($data)
                           ->run()->id();

                // Set primary value with new id.
                $this->data[$tablePrimary] = $id;

                return $id;
            });
        } else {
            // Update action.
            return $this->db->transaction(function () use ($data, $table, $tablePrimary, $id) {
                // Not needed in data set.
                unset($data[$tablePrimary]);

                return $this->initQueryBuilder($table)->update($data)
                            ->whereEqual($tablePrimary, [(int) $id])
                            ->run()->count();
            });
        }
    }

    /**
     * Initializes a new model object by given model name. Throws a `ModelException` if no such
     * model class exists.
     *
     * @param  string                   $name
     * @param  froq\mvc\Controller|null $controller
     * @return froq\mvc\Model (static)
     */
    public final function initModel(string $name, Controller $controller = null): Model
    {
        $class = sprintf('app\model\%sModel', $name);
        if (!class_exists($class)) {
            throw new ModelException(sprintf('Model class "%s" not found', $class));
        }

        return new $class($controller ?? $this->controller, $this->db);
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
     * Initializes a new query builder object using self `$db` property, setting its "table" query
     * with `$table` argument if provided.
     *
     * @param  string $table
     * @return froq\database\QueryBuilder
     */
    public final function initQueryBuilder(string $table = null): QueryBuilder
    {
        return $this->db->initQueryBuilder($table);
    }

    /**
     * Packs table stuff with table name and table primary. Throws a `ModelException` if no
     * `$table` or `$tablePrimary` property defined in subclass.
     *
     * @param  string $method
     * @return array<string>
     */
    private final function packTableStuff(string $method): array
    {
        $table        = $this->getTable();
        $tablePrimary = $this->getTablePrimary();

        if (!$table || !$tablePrimary) {
            throw new ModelException('Both $table and $tablePrimary properties must be '.
                'defined in class "%s" to use "%s()"', [static::class, $method]);
        }

        return [$table, $tablePrimary];
    }
}
