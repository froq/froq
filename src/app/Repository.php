<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app;

use froq\database\{Database, Repository as DatabaseRepository};
use Throwable;

/**
 * Base class of `app\repository` classes.
 *
 * @package froq\app
 * @class   froq\app\Repository
 * @author  Kerem Güneş
 * @since   6.0
 */
class Repository extends DatabaseRepository
{
    /** Namespace of repositories. */
    public final const NAMESPACE = 'app\repository';

    /** Suffix of repositories. */
    public final const SUFFIX    = 'Repository';

    /** Controller instance. */
    public readonly Controller|null $controller;

    /**
     * Constructor.
     *
     * @param  froq\app\Controller|null    $controller
     * @param  froq\database\Database|null $db
     * @throws froq\app\RepositoryException
     */
    public final function __construct(Controller $controller = null, Database $db = null)
    {
        try {
            parent::__construct($db);
        } catch (Throwable $e) {
            throw new RepositoryException($e);
        }

        $this->controller = $controller;

        // Store this repository (as last repository).
        $this->controller?->app::registry()::set('@repository', $this, false);

        // Call init() method if defined in subclass.
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }
}
