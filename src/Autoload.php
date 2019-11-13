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
 * Autoload.
 * @package froq
 * @object  froq\Autoload
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class Autoload
{
    /**
     * Default services.
     * @const array<string>
     */
    public const DEFAULT_SERVICES   = ['MainService', 'FailService'];

    /**
     * Default namespaces.
     * @const array<string>
     */
    public const DEFAULT_NAMESPACES = ['froq\\app\\service', 'froq\\app\\database', 'froq\\app\\library'];

    /**
     * Instance.
     * @var self
     */
    private static $instance;

    /**
     * Constructor.
     * @throws RuntimeException
     */
    private function __construct()
    {
        if (!defined('APP_DIR')) {
            throw new RuntimeException('APP_DIR is not defined!');
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->unregister();
    }

    /**
     * Clone.
     * @throws RuntimeException
     */
    private function __clone()
    {
        throw new RuntimeException('Autoload cannot be cloned, what to do with it cloning?');
    }

    /**
     * Init.
     * @return self
     */
    public static function init(): self
    {
        return self::$instance ? self::$instance : (self::$instance = new self());
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
     * @throws RuntimeException
     */
    public function load(string $name): void
    {
        // Only Froq! stuff.
        if (0 !== strpos($name, 'froq')) {
            return;
        }

        $file = $this->getObjectFile($name);
        if ($file == null) {
            return;
        }

        require $file;
    }

    /**
     * Get object file.
     * @param  string $name
     * @return ?string
     */
    private function getObjectFile(string $name): ?string
    {
        // User service objects (eg: FooService => app/service/FooService/FooService.php).
        if (0 === strpos($name, self::DEFAULT_NAMESPACES[0]) && substr($name, -7) == 'Service') {
            $base = $this->getObjectBase($name);
            $file = in_array($base, self::DEFAULT_SERVICES)
                ? sprintf('%s/app/service/_default/%s/%s.php', APP_DIR, $base, $base)
                : sprintf('%s/app/service/%s/%s.php', APP_DIR, $base, $base);
        }

        // User model objects (eg: FooModel => app/service/FooService/model/model.php).
        elseif (0 === strpos($name, self::DEFAULT_NAMESPACES[1]) && substr($name, -5) == 'Model') {
            $base = $this->getObjectBase(substr($name, 0, -5)) .'Service';
            $file = in_array($base, self::DEFAULT_SERVICES)
                ? sprintf('%s/app/service/_default/%s/model/model.php', APP_DIR, $base)
                : sprintf('%s/app/service/%s/model/model.php', APP_DIR, $base);
        }

        // User library objects (eg: Foo => app/library/Foo.php).
        elseif (0 === strpos($name, self::DEFAULT_NAMESPACES[2])) {
            $file = sprintf('%s/app/library/%s.php', APP_DIR, $this->getObjectBase($name, false));
        }

        // Most objects loaded by Composer, but in case this part is just a fallback.
        else {
            $name   = $this->translateSlashes($name);
            $pkgDir = $this->getFileBase($name);
            $srcDir = substr($name, strlen($pkgDir) + 1);

            // Eg: <app-dir>/vendor/froq/froq-http/src/response/Payload.php
            $file = APP_DIR .'/vendor/froq/'. $pkgDir .'/src/'. $srcDir .'.php';
        }

        $file = $this->translateSlashes($file);

        if (!file_exists($file)) {
            return null;
        }
        return $file;
    }

    /**
     * Get file base.
     * @param  string $name
     * @return string
     */
    private function getFileBase(string $name): string
    {
        $dir = dirname($name);

        $offset = 2;
        // This is exceptional.
        if (0 === strpos($dir, 'froq/http/client')) {
            $offset = 3;
        }

        return implode('-', array_slice(explode('/', $dir), 0, $offset));
    }

    /**
     * Get object base.
     * @param  string $name
     * @param  bool   $endOnly
     * @return string
     */
    private function getObjectBase(string $name, bool $endOnly = true): string
    {
        $tmp = explode('\\', $name);
        $end = array_pop($tmp);

        if ($endOnly) {
            return $end;
        }

        // Eg: froq\app\library\entity\UserEntity => app/library/entity/UserEntity
        $path = join('\\', array_slice($tmp, 3));

        return $path .'\\'. $end;
    }

    /**
     * Translate slashes.
     * @param  string $input
     * @return string
     */
    private function translateSlashes($input): string
    {
        return strtr($input, '\\', '/');
    }
}

// Auto-init as a shorcut for require/include actions.
return Autoload::init();
