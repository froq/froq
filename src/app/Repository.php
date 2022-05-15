<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app;

use froq\database\trait\{DbTrait, EmTrait};
use froq\database\{common\Helper, entity\Manager as EntityManager};
use froq\database\{Database, DatabaseException, Query};
use froq\database\query\{QueryParam, QueryParams};

/**
 * Base class of `app\repository` classes.
 *
 * @package froq\app
 * @object  froq\app\Repository
 * @author  Kerem Güneş
 * @since   6.0
 */
class Repository
{
    use DbTrait, EmTrait;

    /** @var froq\app\Controller */
    public readonly Controller $controller;

    /** @const string */
    public final const NAMESPACE = 'app\repository';

    /** @const string */
    public final const SUFFIX    = 'Repository';

    /**
     * Constructor.
     *
     * @param  froq\app\Controller         $controller
     * @param  froq\database\Database|null $db
     * @throws froq\app\RepositoryException
     */
    public final function __construct(Controller $controller, Database $db = null)
    {
        $this->controller = $controller;

        if (!$db) try {
            $db = Helper::getActiveDatabase();
        } catch (DatabaseException $e) {
            throw new RepositoryException($e->message);
        }

        $this->db = $db;
        $this->em = new EntityManager($db);

        // Call init() method if defined in subclass.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Store this repository (as last repository).
        $this->controller->app::registry()::set('@repository', $this, false);
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
     * @since  6.0
     */
    public final function initQueryParam(): QueryParam
    {
        return new QueryParam();
    }

    /**
     * Init a QueryParams instance.
     *
     * @return froq\database\query\QueryParams
     * @since  6.0
     */
    public final function initQueryParams(): QueryParams
    {
        return new QueryParams();
    }
}
