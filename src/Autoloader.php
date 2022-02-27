<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use RuntimeException;

// Prevent static return collision etc.
function load($file) { require $file; }

/**
 * Autoloader.
 *
 * @package froq\app
 * @object  froq\app\Autoloader
 * @author  Kerem Güneş
 * @since   1.0, 4.0
 */
final class Autoloader
{
    /** @var self */
    private static self $instance;

    /** @var array */
    private static array $directives = [
        'controller' => '/app/system/%s/%s.php',
        'model'      => '/app/system/%s/%s.php|/app/system/%s/model/%s.php',
        'library'    => '/app/library/%s.php',
    ];

    /** @var string */
    private string $directory;

    /**
     * Constructor.
     *
     * @param  string|null $directory
     * @throws RuntimeException
     */
    private function __construct(string $directory = null)
    {
        $directory ??= realpath(__dir__ . '/../../../../vendor/froq');

        if (!$directory || !is_dir($directory)) {
            throw new RuntimeException('Froq folder not found');
        }

        $this->directory = $directory;
    }

    /**
     * Create an Autoloader instance.
     *
     * @param  string|null $directory
     * @return self
     */
    public static function init(string $directory = null): self
    {
        return self::$instance ??= new self($directory);
    }

    /**
     * Register.
     *
     * @return bool
     */
    public function register(): bool
    {
        return spl_autoload_register([$this, 'load']);
    }

    /**
     * Unregister.
     *
     * @return bool
     */
    public function unregister(): bool
    {
        return spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Load a file by its name & namespace.
     *
     * @param  string $name
     * @return void
     */
    public function load(string $name): void
    {
        // Note: seems PHP is calling this just once for found classes,
        // so no need to cache found (resolved) stuff.
        $name = strtr($name, '\\', '/');
        $file = null;

        if (str_starts_with($name, 'app/')) {
            // Controller (eg: app\controller\FooController => app/system/Foo/FooController.php).
            if (str_starts_with($name, 'app/controller/')) {
                $this->checkAppDir();

                if (preg_match('~([A-Z][A-Za-z0-9]+)Controller$~', $name, $match)) {
                    $file = APP_DIR . sprintf(self::$directives['controller'], $match[1], $match[0]);
                }
            }
            // Model (eg: app\model\FooModel => app/system/Foo/FooModel.php or app/system/Foo/model/FooModel.php).
            elseif (str_starts_with($name, 'app/model/')) {
                $this->checkAppDir();

                [$dir, $subdir] = explode('|', self::$directives['model']);

                // A model folder checked for only these classes, eg: Model, FooModel, FooEntity, FooEntityList.
                // So any other classes must be loaded in other ways. Besides, "Model" for only the "Controller"
                // that returned from Router.pack() and called in App.run() to execute callable actions similar
                // to eg: $app->get("/foo/:id", function ($id) { ... }).
                if (preg_match('~([A-Z][A-Za-z0-9]+)Model|ModelException|Entity|EntityList$~', $name, $match)) {
                    $file = APP_DIR . sprintf($dir, $match[1], $match[0]);

                    // Try "model" subdir.
                    is_file($file) || $file = APP_DIR . sprintf($subdir, $match[1], $match[0]);
                }
            }
            // Library (eg: app\library\Foo => app/library/Foo.php).
            elseif (str_starts_with($name, 'app/library/')) {
                $this->checkAppDir();

                $base = substr($name, strlen('app/library/'));
                $file = APP_DIR . sprintf(self::$directives['library'], $base);
            }
        }
        // Most classes loaded by Composer, but in case this part is just a fallback.
        elseif (str_starts_with($name, 'froq/')) {
            [$pkg, $src] = $this->resolve($name);

            $file = $this->directory . '/' . $pkg . '/src/' . $src . '.php';
        }

        if ($file && is_file($file)) {
            load($file);
            return;
        }

        // Note: this part is for only local development purporses,
        // normally Composer will do its job until here.
        static $composerFile, $composerFileData;
        $autoload = defined('APP_DIR');

        // Memoize composer data.
        if ($autoload && !$composerFile) {
            $composerFile = APP_DIR . '/composer.json';
            if (is_file($composerFile)) {
                // Both "psr-4" & "froq" accepted.
                $composerFileData = json_decode(file_get_contents($composerFile), true);
                if (empty($composerFileData['autoload']['psr-4']) &&
                    empty($composerFileData['autoload']['froq'])) {
                    $autoload = false; // Tick.
                } else {
                    $autoload = $composerFileData['autoload']['psr-4']
                             ?? $composerFileData['autoload']['froq'];
                }
            }
        }

        // Try to load via "autoload" directive.
        if ($autoload) {
            $nameOrig = strtr($name, '/', '\\');
            foreach ($autoload as $ns => $dir) {
                if (!str_contains($nameOrig, $ns)) {
                    continue;
                }

                $name = strtr(substr($nameOrig, strlen($ns)), '\\', '/');
                $file = realpath(APP_DIR . '/' . $dir . '/' . $name . '.php');

                if ($file && is_file($file)) {
                    load($file);
                    break;
                }
            }
        }
    }

    /**
     * Check whether APP_DIR is defined.
     */
    private function checkAppDir(): void
    {
        defined('APP_DIR') || throw new RuntimeException(
            'APP_DIR is not defined, required for `app\...` namespaced files'
        );
    }

    /**
     * Resolve package & source by given name.
     */
    private function resolve(string $name): array
    {
        // Base stuffs that stay in "froq/froq".
        static $bases = ['mvc'];

        $dir = dirname($name);
        sscanf($dir, 'froq/%[^/]', $base);

        if (in_array($base, $bases)) {
            $pkg = 'froq';
        } else {
            $dirlen = strlen($dir);

            // Check the calls not for "froq/froq" (4 = strlen("froq")).
            if ($dirlen > 4) {
                $dirlen = strlen($base) + 4 + 1;
            }

            $pkg = substr($dir, 0, $dirlen);
        }

        // Eg: "froq/acl" => "froq-acl".
        $pkg = strtr($pkg, '/', '-');
        $src = substr($name, strlen($pkg) + 1);

        return [$pkg, $src];
    }
}
