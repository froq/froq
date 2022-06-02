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
 * and separable from "save/remove" actions. But besides, this class provides basically
 * other CRUD functionalities.
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

    /** @var string */
    protected string $table;

    /** @var froq\database\Query */
    private readonly Query $query;

    /**
     * Constructor.
     *
     * @param  froq\database\Database|null $db
     * @param  string|null                 $table
     * @throws froq\app\data\QueryFactoryException
     */
    public function __construct(Database $db = null, string $table = null)
    {
        if (!$db) try {
            // Real caller method for a proper error message.
            $caller = static::class . '::' . __function__;

            $db = DatabaseRegistry::getDefault($caller);
        } catch (DatabaseRegistryException $e) {
            throw new QueryFactoryException($e->message);
        }

        $this->db = $db;

        // Can be defined in subclass.
        $table && $this->table = $table;

        // Can also be defined as constant in subclass.
        if (!isset($this->table) && constant_exists($this, 'TABLE')) {
            $this->table = $this::TABLE;
        }
    }

    /**
     * When a proxy method is not defined in subclass (eg: `withLimit()`) this method
     * will handle such these methods dynamically creating/assigning `$query` property
     * and using it/its methods. For more query building functionalities, you can see
     * `froq\database\Query` class.
     *
     * Note: All proxy (absent) methods must be prefixed as "with". So, for calling
     * query's `between()` method, caller method must be like `withBetween()`.
     *
     * For example for a `BookQuery` class, a query building can be done like:
     *
     * ```
     * // Selecting books by date with limit as Book entity.
     * $books = BookQuery::new()
     *  ->withGreaterThan('date', '2010-01-01')
     *  ->withFetch(Book::class)
     *  ->withLimit(3)
     *  ->select('*')
     *  ->getAll();
     *
     * // Selecting a book by id.
     * $book = BookQuery::new()
     *   ->withEqual('id', 1)
     *   // Or simply.
     *   // ->where('id', [1])
     *   ->select()
     *   ->get();
     * ```
     *
     * @param  string $name
     * @param  array  $arguments
     * @return self
     * @throws froq\app\data\QueryFactoryException
     */
    public function __call(string $name, array $arguments = []): self
    {
        if (!str_starts_with($name, 'with')) {
            throw new QueryFactoryException(
                'Invalid call as %s::%s()', [static::class, $name]
            );
        }

        // Eg: withLimit(1) => query.limit(1).
        $name = substr($name, 4);

        $this->query()->$name(...$arguments);

        return $this;
    }

    /**
     * Get table property.
     *
     * @return string|null
     */
    public final function table(): string|null
    {
        return $this->table ?? null;
    }

    /**
     * Get query property.
     *
     * Note: This method assigns query property on-demand for once. So, in case
     * for using query property multiple times, `reset()` method must be called.
     *
     * @return froq\database\Query
     */
    public final function query(): Query
    {
        return $this->query ??= $this->initQuery();
    }

    /**
     * Init a Query instance.
     *
     * @param  string|null $table
     * @return froq\database\Query
     */
    public final function initQuery(string $table = null): Query
    {
        return new Query($this->db, $table ?? $this->table ?? null);
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

    /**
     * Proxy method to query's select().
     *
     * @see froq\database\Query::select()
     */
    public function select(...$args): self
    {
        $this->query()->select(...$args);

        return $this;
    }

    /**
     * Proxy method to query's insert().
     *
     * @see froq\database\Query::insert()
     */
    public function insert(...$args): self
    {
        $this->query()->insert(...$args);

        return $this;
    }

    /**
     * Proxy method to query's update().
     *
     * @see froq\database\Query::update()
     */
    public function update(...$args): self
    {
        $this->query()->update(...$args);

        return $this;
    }

    /**
     * Proxy method to query's delete().
     *
     * @see froq\database\Query::delete()
     */
    public function delete(...$args): self
    {
        $this->query()->delete(...$args);

        return $this;
    }

    /**
     * Proxy method to query's where().
     *
     * @see froq\database\Query::where()
     */
    public function where(...$args): self
    {
        $this->query()->where(...$args);

        return $this;
    }

    /**
     * Proxy method to query's group().
     *
     * @see froq\database\Query::group()
     */
    public function group(...$args): self
    {
        $this->query()->group(...$args);

        return $this;
    }

    /**
     * Proxy method to query's order().
     *
     * @see froq\database\Query::order()
     */
    public function order(...$args): self
    {
        $this->query()->order(...$args);

        return $this;
    }

    /**
     * Proxy method to query's limit().
     *
     * @see froq\database\Query::limit()
     */
    public function limit(...$args): self
    {
        $this->query()->limit(...$args);

        return $this;
    }

    /**
     * Proxy method to query's offset().
     *
     * @see froq\database\Query::offset()
     */
    public function offset(...$args): self
    {
        $this->query()->offset(...$args);

        return $this;
    }

    /**
     * Proxy method to query's get().
     *
     * @see froq\database\Query::get()
     */
    public function get(...$args)
    {
        return $this->query()->get(...$args);
    }

    /**
     * Proxy method to query's getAll().
     *
     * @see froq\database\Query::getAll()
     */
    public function getAll(...$args)
    {
        return $this->query()->getAll(...$args);
    }

    /**
     * Proxy method to query's paginate().
     *
     * @see froq\database\Query::paginate()
     */
    public function paginate(...$args)
    {
        return $this->query()->paginate(...$args);
    }

    /**
     * Proxy method to query's run().
     *
     * @see froq\database\Query::run()
     */
    public function run(...$args)
    {
        return $this->query()->run(...$args);
    }

    /**
     * Proxy method to query's count().
     *
     * @see froq\database\Query::count()
     */
    public function count()
    {
        return $this->query()->count();
    }

    /**
     * Reset query stack.
     *
     * @return void
     */
    public function reset(): void
    {
        if (isset($this->query)) {
            $this->query->reset();
            if (isset($this->table)) {
                $this->query->table($this->table);
            }
        }
    }

    /**
     * Static initializer.
     *
     * @param  mixed ...$args
     * @return static
     */
    public static function new(mixed ...$args): static
    {
        return new static(...$args);
    }
}
