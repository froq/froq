<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app;

use froq\database\{Database, Repository as DatabaseRepository};
use froq\common\{interface\Reflectable, trait\ReflectTrait};
use froq\Autoloader;
use State;

/**
 * Base class of `app\repository` classes.
 *
 * @package froq\app
 * @class   froq\app\Repository
 * @author  Kerem Güneş
 * @since   6.0
 */
class Repository extends DatabaseRepository implements Reflectable
{
    use ReflectTrait;

    /** Namespace of repositories. */
    public final const NAMESPACE = 'app\repository';

    /** Suffix of repositories. */
    public final const SUFFIX    = 'Repository';

    /** Controller instance. */
    public readonly Controller|null $controller;

    /** Dynamic state reference. */
    public readonly State $state;

    /**
     * Constructor.
     *
     * @param  froq\app\Controller|null    $controller
     * @param  froq\database\Database|null $db
     * @throws froq\app\RepositoryException
     */
    public function __construct(Controller $controller = null, Database $db = null)
    {
        try {
            parent::__construct($db);
        } catch (\Throwable $e) {
            throw new RepositoryException($e);
        }

        $this->controller = $controller;
        $this->state      = new State();

        // Store this repository (as last repository).
        $this->controller?->app::registry()::setRepository($this, false);

        // Call init() method if defined in subclass.
        if (method_exists($this, 'init')) {
            $this->init();
        }

        // Load data files if any.
        $this->loadDataFiles();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        // Call dinit() method if defined in subclass.
        if (method_exists($this, 'dinit')) {
            $this->dinit();
        }
    }

    /**
     * Init a Repository instance.
     *
     * @param  string                   $name
     * @param  froq\app\Controller|null $controller
     * @return froq\app\Repository
     */
    public function initRepository(string $name, Controller $controller = null): Repository
    {
        $controller ??= $this->controller ?? new Controller();

        return $controller->initRepository($name, $controller, $controller->app->database);
    }

    /**
     * Load data files in "/data" directory (eg: /Book/data/{BookDto.php, ...}).
     *
     * @return void
     */
    public function loadDataFiles(): void
    {
        // Once for every subclass.
        static $done;

        // Should subclass call be used.
        if (($key = static::class) === self::class) {
            return;
        }

        if (empty($done[$key])) {
            $done[$key] = true;

            // Subclass reflection.
            $that = $this->reflect(true);

            // Path of data directory of that repository.
            $path = xpath($that->getDirectoryName() . '/data');

            if ($path->isDirectory()) {
                $section = '@data';
                $autoloader = Autoloader::init();
                if (!$autoloader->getClassMap($section)) {
                    $autoloader->addClassMap(
                        $autoloader->generateClassMap($path->getName()),
                        $section
                    );
                }
            }
        }
    }
}
