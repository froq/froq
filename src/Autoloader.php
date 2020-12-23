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
 * @since   1.0, 4.0 Renamed from Autoload, refactored.
 */
final class Autoloader
{
    /** @var self */
    private static self $instance;

    /** @var array */
    private static array $directives = [
        0 => ['app/controller', '/app/system/%s/%s.php'],
        1 => ['app/model'     , '/app/system/%s/model/%s.php'],
        2 => ['app/library'   , '/app/library/%s.php'],
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
        $directory = $directory ?? realpath(__dir__ . '/../../../../vendor/froq');

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
        // Note: seems PHP is calling this just once for found classes, so no need to cache found
        // (resolved) stuff.

        $name = strtr($name, '\\', '/');
        $file = null;

        if (str_starts_with($name, 'app/')) {
            // User controller objects (eg: FooController => app/system/Foo/FooController.php).
            if (str_starts_with($name, self::$directives[0][0])) {
                $this->checkAppDir();

                preg_match('~([A-Z][a-zA-Z0-9]+)Controller$~', $name, $match);
                if ($match) {
                    $file = APP_DIR . sprintf(self::$directives[0][1], $match[1], $match[0]);
                }
            }
            // User model objects (eg: FooModel => app/system/Foo/model/FooModel.php).
            elseif (str_starts_with($name, self::$directives[1][0])) {
                $this->checkAppDir();

                // A model folder checked for only these objects, eg: Model, FooModel, FooEntity, FooEntityArray.
                // So any other objects must be loaded in other ways. Besides, "Model" for only the "Controller"
                // that returned from Router.pack() and called in App.run() to execute callable actions similar
                // to eg: $app->get("/book/:id", function ($id) { ... }).
                preg_match('~([A-Z][a-zA-Z0-9]+)(Model|ModelException|Entity|EntityArray)$~', $name, $match);
                if ($match) {
                    $file = APP_DIR . sprintf(self::$directives[1][1], $match[1], $match[0]);
                }
            }
            // User library objects (eg: Foo => app/library/Foo.php).
            elseif (str_starts_with($name, self::$directives[2][0])) {
                $this->checkAppDir();

                $base = substr($name, strlen(self::$directives[2][0]) + 1);
                $file = APP_DIR . sprintf(self::$directives[2][1], $base);
            }
        }
        // Most objects loaded by Composer, but in case this part is just a fallback.
        elseif (str_starts_with($name, 'froq/')) {
            [$pkg, $src] = $this->resolve($name);

            $file = $this->directory . '/' . $pkg . '/src/' . $src . '.php';
        }

        if ($file && is_file($file)) {
            load($file);
            return;
        }

        // Note: this part is for only local development purporses, normally Composer will
        // do its job until here.

        static $autoload; $autoload ??= defined('APP_DIR');

        // Memoize autoload data.
        if ($autoload !== false) {
            $composerFile = APP_DIR . '/composer.json';
            if (is_file($composerFile)) {
                // Both "psr-4" & "froq" accepted.
                $composerFileData = json_decode(file_get_contents($composerFile), true);
                if (empty($composerFileData['autoload']['psr-4'])
                    && empty($composerFileData['autoload']['froq'])) {
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
                $file = APP_DIR . '/' . $dir . '/' . $name . '.php';

                if (is_file($file)) {
                    load($file);
                    return;
                }
            }
        }
    }

    /**
     * Check whether APP_DIR is defined.
     *
     * @return void
     * @throws RuntimeException
     */
    private function checkAppDir(): void
    {
        defined('APP_DIR') || throw new RuntimeException(
            'APP_DIR is not defined, it is required for `app\...` namespaced files'
        );
    }

    /**
     * Resolve package & source by given name.
     *
     * @param  string $name
     * @return array<string>
     */
    private function resolve(string $name): array
    {
        // Base stuffs that stay in "froq/froq".
        static $bases = ['mvc'];

        $dir = dirname($name);
        sscanf($dir, 'froq/%[^/]', $base);

        $isBase = in_array($base, $bases, true);
        if ($isBase) {
            $pkg = 'froq';
        } else {
            $dirlen = strlen($dir);

            // Check the calls not for "froq/froq" (4 = strlen("froq")).
            if ($dirlen > 4) {
                $dirlen = strlen($base) + 4 + 1;
            }

            $pkg = substr($dir, 0, $dirlen);
        }

        $pkg = strtr($pkg, '/', '-'); // Eg: "froq/acl" => "froq-acl".
        $src = substr($name, strlen($pkg) + 1);

        return [$pkg, $src];
    }
}
