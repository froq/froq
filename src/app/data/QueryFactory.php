<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app\data;

use froq\database\trait\DbTrait;
use froq\database\{Database, DatabaseRegistry, DatabaseRegistryException, Query};
use froq\database\query\{QueryParam, QueryParams};

/**
 * Query factory class for query related works in repositories these for "find" actions
 * and separable from "save/remove" actions.
 *
 * Example: For a `BookRepository` class, a query class can be declared as `BookQuery`
 * in same namespace and created in `BookRepository.init()` method to use in `find*()`
 * methods to separate query works/business.
 *
 * ```
 * // BookRepository.init()
 * $this->query = new BookQuery()
 *
 * // BookRepository.find()
 * return $this->query->find($id)
 *
 * // BookQuery.find()
 * return $this->initQuery('books')->select('*')->where('id', [$id])->get()
 * ```
 *
 * @package froq\app\data
 * @object  froq\app\data\QueryFactory
 * @author  Kerem Güneş
 * @since   6.0
 */
class QueryFactory
{
    use DbTrait;

    /**
     * Constructor.
     *
     * @param  froq\database\Database|null $db
     * @throws froq\app\data\QueryFactoryException
     */
    public function __construct(Database $db = null)
    {
        if (!$db) try {
            // Real caller method for a proper error message.
            $caller = static::class . '::' . __function__;

            $db = DatabaseRegistry::getDefault($caller);
        } catch (DatabaseRegistryException $e) {
            throw new QueryFactoryException($e->message);
        }

        $this->db = $db;
    }

    /**
     * Init a Query instance.
     *
     * @param  string|null $table
     * @return froq\database\Query
     */
    public final function initQuery(string $table = null): Query
    {
        return new Query($this->db, $table);
    }

    /**
     * Init a QueryParam instance.
     *
     * @return froq\database\query\QueryParam
     */
    public final function initQueryParam(): QueryParam
    {
        return new QueryParam();
    }

    /**
     * Init a QueryParams instance.
     *
     * @return froq\database\query\QueryParams
     */
    public final function initQueryParams(): QueryParams
    {
        return new QueryParams();
    }
}
