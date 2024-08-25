<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

// Prevent static return collision etc.
function load($file, $once = false) {
    $once ? require_once $file : require $file;
}

/**
 * Autoloader.
 *
 * @package froq
 * @class   froq\Autoloader
 * @author  Kerem Güneş
 * @since   1.0, 4.0
 */
class Autoloader
{
    /** Directives for "app" related files. */
    public const DIRECTIVES = [
        'controller' => '/app/system/%s/%s.php|/app/system/%s.php',
        'repository' => '/app/system/%s/%s.php|/app/system/%s/data/%s.php|/app/system/%s.php',
    ];

    /** Instance. */
    private static self $instance;

    private static array $mapCache = [];
    private static array $nameCache = [];

    /** Generated class map. */
    private static array $classMap = [];

    /** Vendor (Froq!) directory. */
    private string $directory;

    /**
     * Constructor.
     *
     * @param  string|null $directory
     * @throws Exception
     */
    private function __construct(string $directory = null)
    {
        $directory = realpath($directory ?? __DIR__ . '/../../../../vendor/froq');

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
        // Check for name tick.
        if ($this->checkNameCache($name)) {
            return;
        }

        if ($file = $this->resolveFile($name)) {
            load($file);
            return;
        }

        // Allow capital-case names.
        $nameLC = $this->lowerizeNamespace($name);

        if ($file = $this->resolveFile($nameLC)) {
            load($file);
            return;
        }

        // This part is for only local development purporses.
        // So, normally Composer would do its job until here.
        if (defined('APP_DIR')) {
            static $psr4;
            $psr4 ??= (function () {
                if (is_file($json = APP_DIR . '/composer.json')) {
                    $data = json_decode(file_get_contents($json), true);
                    return $data['autoload']['psr-4'] ?? false;
                }
                return false;
            })();

            if ($psr4) {
                static $find;
                $find ??= function ($name) use ($psr4) {
                    $name = strtr($name, '/', '\\');
                    foreach ($psr4 as $namespace => $directory) {
                        if (str_starts_with($name, $namespace)) {
                            $name = strtr(substr($name, strlen($namespace)), '\\', '/');
                            $file = APP_DIR . '/' . $directory . '/' . $name . '.php';
                            if (is_file($file)) {
                                return $file;
                            }
                        }
                    }
                };

                if ($file = $find($name) ?: $find($nameLC)) {
                    load($file);
                }
            }
        }
    }

    /**
     * Lazy load for non-existent classes, interfaces, traits & enums.
     *
     * @param  string $name
     * @return void
     * @since  6.0
     */
    public function loadLazy(string $name): void
    {
        if (!class_exists($name, false) && !interface_exists($name, false)
            && !trait_exists($name, false) && !enum_exists($name, false)) {
            $this->load($name);
        }
    }

    /**
     * Load sugars (global helper functions) from util module.
     *
     * Note: For tests only (see sample/test/boot.php).
     *
     * @return void
     * @throws Exception
     */
    public function loadSugars(): void
    {
        is_file($file = $this->directory . '/froq-util/src/sugars.php')
            ? load($file, true) : throw new \Exception("Froq sugars file '{$file}' not found");
    }

    /**
     * Explore app directories (app/system & app/library) and generate an autoload
     * map writing all found classes, interfaces, traits & enums into static file.
     *
     * Note: This method must be used via bin/explore.php file on console. To drop
     * current autoload map, `--drop` option can be used.
     *
     * Examples:
     * $ php -f bin/explore.php OR $ composer explore
     * $ php -f bin/explore.php -- --no-sort OR $ composer explore -- --no-sort
     * $ php -f bin/explore.php -- --drop OR $ composer explore -- --drop # For cleaning.
     *
     * @param  string $directory
     * @param  array  $options
     * @return void
     * @throws Exception
     * @since  6.0
     */
    public function explore(string $directory, array $options = []): void
    {
        $this->checkAppDir();

        $mapTpl = <<<TPL
        <?php
        // Autogenerated by froq\\Autoloader::explore() method at @at.
        // This file will be overwritten & merged with its contents for each call to this method.
        return @map;
        TPL;
        $mapFile = APP_DIR . $this->getMapFile();

        // Options with defaults.
        $options = array_replace(['sort' => true, 'drop' => false], $options);

        // Drop map file.
        if ($options['drop']) {
            @unlink($mapFile);
            return;
        }

        // Prepend APP_DIR to directory & check.
        if (!$directoryReal = realpath(APP_DIR . $directory)) {
            throw new \Exception('No directory exists such ' . APP_DIR . $directory);
        }

        $map = $this->generateClassMap($directoryReal);

        // Merge old map (if exists) with generated map.
        if ($oldMap = $this->getMap(false)) {
            $map = [...$oldMap, ...$map];
        }

        // Sort map items (moving library items down).
        if ($options['sort']) {
            uksort($map, function ($a, $b) {
                $x = +str_starts_with($a, 'app\library');
                $y = +str_starts_with($b, 'app\library');
                return ($x - $y) ?: strcmp($a, $b);
            });
        }

        $map = var_export($map, true);
        $map = str_replace('\\\\', '\\', $map);
        $map = str_replace(['array (', ')'], ['[', ']'], $map);
        $map = str_replace(['@at', '@map'], [date('r'), $map], $mapTpl);

        // Write map file contents & check.
        file_put_contents($mapFile, $map) ?: throw new \Exception('Cannot write map file ' . $mapFile);
    }

    /**
     * Get class map in given directory.
     *
     * @param  string $directory
     * @return array
     */
    public function generateClassMap(string $directory, string $pattern = null): array
    {
        $pattern ??= '~.+/[A-Z][A-Za-z0-9]+\.php$~'; // Files starting with upper-case only.
        $flags = \RecursiveRegexIterator::MATCH; // For collecting file info stack only.

        /** @var RegexIterator<SplFileInfo> */
        $infos = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            ),
            $pattern, $flags
        );

        $map = [];

        foreach ($infos as $info) {
            // Reset for each file.
            $index = 0; $item = [];

            $file = $info->getRealPath();
            $tokens = \PhpToken::tokenize(file_get_contents($file));

            foreach ($tokens as $token) {
                // Class, interface, trait, enum only.
                if (!$token->is(['namespace', 'class', 'interface', 'trait', 'enum'])) {
                    $index++;
                    continue;
                }

                $index++;

                // Grab namespace.
                if ($token->is('namespace')) {
                    $item['namespace'] = $tokens[$index + 1]->text;
                }
                // Grab name (and prepend namespace if exists).
                elseif ($token->is(['class', 'interface', 'trait', 'enum'])) {
                    $item['name'] = $tokens[$index + 1]->text;
                    if (isset($item['namespace'])) {
                        $item['name'] = $item['namespace'] . '\\' . $item['name'];
                    }
                }

                // Add item to map as key/value pairs (fully qualified name / full file path).
                if (isset($item['namespace'], $item['name']) && !isset($map[$item['name']])) {
                    // Validate name. @fix: Somehow some invalid names come from somewhere, sorry..
                    if (preg_match('~^[\w\\\]+$~', $item['name'])) {
                        $map[$item['name']] = $file;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Add class map, optionally with a section.
     *
     * @param  array       $classMap
     * @param  string|null $section
     * @return void
     */
    public static function addClassMap(array $classMap, string $section = null): void
    {
        if ($section === null) {
            self::$classMap += $classMap;
        } else {
            self::$classMap[$section] ??= [];
            self::$classMap[$section] += $classMap;
        }
    }

    /**
     * Get class map, optionally with a section.
     *
     * @param  string|null $section
     * @return array
     */
    public static function getClassMap(string $section = null): array
    {
        if ($section === null) {
            return self::$classMap;
        }
        return self::$classMap[$section] ?? [];
    }

    /**
     * Get map, optionally using cache.
     *
     * @param  bool $cache
     * @return array|null
     * @since  6.0
     */
    public function getMap(bool $cache = true): array|null
    {
        // To speed up load() method.
        if ($cache && !empty(self::$mapCache)) {
            return self::$mapCache;
        }

        if (defined('APP_DIR') && is_file($mapFile = APP_DIR . $this->getMapFile())) {
            // Drop file from opcache, in case touched by anyone.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($mapFile, true);
            }

            if (is_array($map = include $mapFile)) {
                if ($cache && !empty($map)) {
                    self::$mapCache = $map;
                }

                return $map;
            }
        }

        return null;
    }

    /**
     * Get map file, as static.
     *
     * @return string
     * @since  6.0
     */
    public function getMapFile(): string
    {
        return '/var/autoload.map';
    }

    /**
     * Get a file by given name that mapped by `explore()` method.
     *
     * @param  string $name
     * @return string|null
     * @since  6.0
     */
    public function getMappedFile(string $name): string|null
    {
        if ($file = $this->getMap()[$name] ?? '') {
            return $file;
        }

        foreach (self::$classMap as $class => $file) {
            // Regular entries.
            if ($class === $name) {
                return $file;
            }

            // Section entries.
            if (is_array($file)) {
                foreach ($file as $class => $file) {
                    if ($class === $name) {
                        return $file;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get finding a file by given (FQ) name.
     *
     * @param  string $name
     * @return string|null
     * @causes Exception
     */
    public function getFile(string $name): string|null
    {
        $file = null;
        $name = strtr($name, '\\', '/');

        // Controller (eg: app\controller\FooController => app/system/Foo/FooController.php
        //                                              or app/system/FooController.php).
        if (str_starts_with($name, 'app/controller/')) {
            $this->checkAppDir();

            if (preg_match('~([A-Z][A-Za-z0-9]+)Controller$~', $name, $match)) {
                [$dir, $supdir] = explode('|', self::DIRECTIVES['controller']);

                $file = APP_DIR . sprintf($dir, $match[1], $match[0]);

                // Try with "system" supdir (eg: app/system/FooController.php).
                is_file($file) || $file = APP_DIR . sprintf($supdir, $match[0]);
            }
        }
        // Repository (eg: app\repository\FooRepository => app/system/Foo/FooRepository.php
        //                                              or app/system/Foo/data/FooRepository.php
        //                                              or app/system/FooRepository.php).
        elseif (str_starts_with($name, 'app/repository/')) {
            $this->checkAppDir();

            // Data folder checked for only such these classes: FooRepository, FooEntity, FooEntityList, FooQuery, FooSearch.
            // So, any other classes must be loaded in other ways. Besides, "Repository" for only "Controller"
            // that returned from Router.pack() and called in App.run() to execute callable actions similar
            // to eg: $app->get("/foo/:id", function ($id) { ... }).
            if (preg_match('~([A-Z][A-Za-z0-9]+)(?:Repository|Entity|EntityList|Query|Search|Resource|Dto|DTO)$~', $name, $match)) {
                [$dir, $subdir, $supdir] = explode('|', self::DIRECTIVES['repository']);

                $file = APP_DIR . sprintf($dir, $match[1], $match[0]);

                // Try with "data" subdir (eg: app/system/Foo/data/FooRepository.php).
                is_file($file) || $file = APP_DIR . sprintf($subdir, $match[1], $match[0]);

                // Try with "system" supdir (eg: app/system/FooRepository.php).
                is_file($file) || $file = APP_DIR . sprintf($supdir, $match[0]);
            }
        }
        // Library (eg: app\library\Foo => app/library/Foo.php).
        // Service (eg: app\service\Foo => app/service/Foo.php).
        elseif (str_starts_with($name, 'app/library/') || str_starts_with($name, 'app/service/')) {
            $this->checkAppDir();

            $file = APP_DIR . '/' . $name . '.php';
        }
        // Most classes loaded by Composer, but just fallback.
        elseif (str_starts_with($name, 'froq/')) {
            [$pkg, $src] = $this->resolve($name);

            $file = $this->directory . '/' . $pkg . '/src/' . $src . '.php';
        }

        return $file;
    }

    /**
     * Resolve a file by given name with/without using map.
     *
     * @param  string $name
     * @return string|null
     * @causes Exception
     * @since  6.1
     */
    public function resolveFile(string $name): string|null
    {
        $file = (
            // Try to load from autoload map.
            $this->getMappedFile($name) ?:
            // Try to load app & froq files.
            $this->getFile($name)
        );

        return ($file && is_file($file)) ? $file : null;
    }

    /**
     * Check whether APP_DIR is defined.
     *
     * @throws Exception
     */
    private function checkAppDir(): void
    {
        defined('APP_DIR') || throw new \Exception(
            "APP_DIR is not defined, required for 'app\...' namespaced files"
        );
    }

    /**
     * Check whether name was cached and tick it.
     *
     * @param  string $name
     * @return bool
     */
    private function checkNameCache(string $name): bool
    {
        if (isset(self::$nameCache[$name])) {
            return true;
        }

        // Add tick.
        self::$nameCache[$name] = 1;

        return false;
    }

    /**
     * Change namespace as lower-cased, keeping basename original.
     */
    private function lowerizeNamespace(string $name): string
    {
        if (preg_match('~^(?:App|Froq)([/\\\])~i', $name, $match)) {
            $spos = strrpos($name, $match[1]);
            $name = strtolower(substr($name, 0, $spos)) // Namespace.
                . $match[1] . substr($name, $spos + 1); // Basename.
        }

        return $name;
    }

    /**
     * Resolve package & source by given name.
     */
    private function resolve(string $name): array
    {
        // Base stuff defined in "froq/froq".
        static $bases = ['app' => 1, 'http' => 1];

        $dir = substr($name, 0, strrpos($name, '/'));
        sscanf($dir, 'froq/%[^/]', $base);

        if (isset($bases[$base])) {
            $pkg = 'froq';
        } else {
            $dirlen = strlen($dir);

            // Not "froq/froq" (4 = strlen("froq")).
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
