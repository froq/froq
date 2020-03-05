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

namespace froq;

use RuntimeException;

/**
 * Autoloader.
 * @package froq\app
 * @object  froq\app\Autoloader
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0, 4.0 Refactored, renamed as Autoloader from Autoload.
 */
final class Autoloader
{
    /**
     * Instance.
     * @var self
     */
    private static self $instance;

    /**
     * Directory.
     * @var string
     */
    private string $directory;

    /**
     * Locations.
     * @var array<int, array<string>>
     */
    private array $locations = [
        0 => ['app/controller', '/app/system/%s/%s.php'],
        1 => ['app/model'     , '/app/system/%s/model/%s.php'],
        2 => ['app/library'   , '/app/library/%s.php'],
    ];

    /**
     * Constructor.
     * @param  string|null $directory
     * @throws RuntimeException
     */
    private function __construct(string $directory = null)
    {
        $directory = $directory ?? realpath(__dir__ .'/../../../../vendor/froq');

        if (!$directory || !is_dir($directory)) {
            throw new RuntimeException('Froq folder not found');
        }

        $this->directory = $directory;
    }

    /**
     * Init.
     * @param  string|null $directory
     * @return self
     */
    public static function init(string $directory = null): self
    {
        return self::$instance ?? (self::$instance = new self($directory));
    }

    /**
     * Register.
     * @return bool
     */
    public function register(): bool
    {
        return spl_autoload_register([$this, 'load']);
    }

    /**
     * Unregister.
     * @return bool
     */
    public function unregister(): bool
    {
        return spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Load.
     * @param  string $name
     * @return void
     */
    public function load(string $name): void
    {
        // Note: seems PHP is calling this just once for found classes, so no need to cache found
        // (resolved) stuff.

        $name = strtr($name, '\\', '/');
        $file = null;

        if (strpos($name, 'app/') === 0) {
            // User controller objects (eg: FooController => app/system/Foo/FooController.php).
            if (strpos($name, $this->locations[0][0]) === 0) {
                $this->checkAppDir();

                preg_match('~([A-Z][a-zA-Z0-9]+)Controller$~', $name, $match);
                if ($match) {
                    $file = APP_DIR . sprintf($this->locations[0][1], $match[1], $match[0]);
                }
            }
            // User model objects (eg: FooModel => app/system/Foo/model/FooModel.php).
            elseif (strpos($name, $this->locations[1][0]) === 0) {
                $this->checkAppDir();

                // A model folder checked for only these objects, eg: Model, FooModel, FooEntity, FooEntityArray.
                // So any other objects must be loaded in other ways. Besides, "Model" for only the "Controller"
                // that returned from Router.pack() and called in App.run() to execute callable actions similar
                // to eg: $app->get("/book/:id", function ($id) { ... }).
                preg_match('~([A-Z][a-zA-Z0-9]+)(Model|Entity|EntityArray)$~', $name, $match);
                if ($match) {
                    $file = APP_DIR . sprintf($this->locations[1][1], $match[1], $match[0]);
                }
            }
            // User library objects (eg: Foo => app/library/Foo.php).
            elseif (strpos($name, $this->locations[2][0]) === 0) {
                $this->checkAppDir();

                $base = substr($name, strlen($this->locations[2][0]) + 1);
                $file = APP_DIR . sprintf($this->locations[2][1], $base);
            }
        }
        // Most objects loaded by Composer, but in case this part is just a fallback.
        elseif (strpos($name, 'froq') === 0) {
            [$package, $source] = $this->resolve($name);

            $file = $this->directory .'/'. $package .'/src/'. $source .'.php';
        }

        if ($file && is_file($file)) {
            require $file;
        }
    }

    /**
     * Check app dir.
     * @return void
     */
    private function checkAppDir(): void
    {
        if (!defined('APP_DIR')) {
            throw new RuntimeException('APP_DIR is not defined, it is required for "app\..." '.
                'namespaced files');
        }
    }

    /**
     * Resolve.
     * @param  string $name
     * @return array<string, string>
     */
    private function resolve(string $name): array
    {
        // Base stuffs that stay in "froq/froq".
        static $bases = ['mvc'];

        $dir    = dirname($name);
        $dirlen = strlen($dir);

        sscanf($dir, 'froq/%[^/]', $base);
        if (in_array($base, $bases)) {
            $dirlen -= strlen($base) + 1;
        } else {
            // Check the calls not for "froq/froq" (4 = strlen("froq")).
            if (strlen($dir) > 4) {
                $dirlen = strlen($base) + 4 + 1;
            }
        }

        $package = substr($dir, 0, $dirlen);
        $source  = substr($name, strlen($package) + 1);

        // Eg: "froq/database" to "froq-database".
        $package = strtr($package, '/', '-');

        return [$package, $source];
    }
}
