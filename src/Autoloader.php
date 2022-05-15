<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

// Prevent static return collision etc.
function load($file) { require $file; }

/**
 * Autoloader.
 *
 * @package froq
 * @object  froq\Autoloader
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
        'repository' => '/app/system/%s/%s.php|/app/system/%s/data/%s.php',
    ];

    /** @var string */
    private string $directory;

    /**
     * Constructor.
     *
     * @param  string|null $directory
     * @throws Exception
     */
    private function __construct(string $directory = null)
    {
        $directory ??= realpath(__dir__ . '/../../../../vendor/froq');

        if (!$directory || !is_dir($directory)) {
            throw new \Exception('Froq folder not found');
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
     * Check given names to ensure calling load.
     *
     * Note: This method for only development purposes.
     *
     * @param  string ...$names
     * @return void
     * @since  6.0
     */
    public static function check(string ...$names): void
    {
        foreach ($names as $name) {
            class_exists($name) || interface_exists($name) ||
            trait_exists($name) || enum_exists($name);
        }
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
        // Seems PHP is calling this just once for found classes,
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
            // Data (eg: app\data\FooRepository => app/system/Foo/FooRepository.php or app/system/Foo/data/FooRepository.php).
            elseif (str_starts_with($name, 'app/data/') || str_starts_with($name, 'app/repository/')) {
                $this->checkAppDir();

                [$dir, $subdir] = explode('|', self::$directives['repository']);

                // A data folder checked for only such these classes: FooRepository, FooEntity, FooEntityList.
                // Any other classes must be loaded in other ways. Besides, "Repository" for only "Controller"
                // that returned from Router.pack() and called in App.run() to execute callable actions similar
                // to eg: $app->get("/foo/:id", function ($id) { ... }).
                if (preg_match('~([A-Z][A-Za-z0-9]+)(?:Repository|Entity|EntityList)$~', $name, $match)) {
                    $file = APP_DIR . sprintf($dir, $match[1], $match[0]);

                    // Try with "data" subdir (eg: app/system/Foo/data/FooRepository.php).
                    is_file($file) || $file = APP_DIR . sprintf($subdir, $match[1], $match[0]);
                }
            }
            // Library (eg: app\library\Foo => app/library/Foo.php).
            elseif (str_starts_with($name, 'app/library/')) {
                $this->checkAppDir();

                $file = APP_DIR . '/' . $name . '.php';
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

        // This part is for only local development purporses,
        // normally Composer would do its job until here.
        if (defined('APP_DIR')) {
            static $autoload; $autoload ??= (function () {
                if (is_file($json = APP_DIR . '/composer.json')) {
                    $data = json_decode(file_get_contents($json), true);
                    return $data['autoload']['psr-4'] ?? false;
                }
                return false;
            })();

            if ($autoload) {
                static $find; $find ??= function ($name) use ($autoload) {
                    $name = strtr($name, '/', '\\');
                    foreach ($autoload as $namespace => $folder) {
                        if (str_starts_with($name, $namespace)) {
                            $name = strtr(substr($name, strlen($namespace)), '\\', '/');
                            $file = APP_DIR . '/' . $folder . '/' . $name . '.php';
                            if (is_file($file)) {
                                return $file;
                            }
                        }
                    }
                };

                $file = $find($name);
                $file && load($file);
            }
        }
    }

    /**
     * Check whether APP_DIR is defined.
     */
    private function checkAppDir(): void
    {
        defined('APP_DIR') || throw new \Exception(
            'APP_DIR is not defined, required for `app\...` namespaced files'
        );
    }

    /**
     * Resolve package & source by given name.
     */
    private function resolve(string $name): array
    {
        // Base stuffs defined in "froq/froq".
        static $bases = ['app'];

        $dir = dirname($name);
        sscanf($dir, 'froq/%[^/]', $base);

        if (in_array($base, $bases, true)) {
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
