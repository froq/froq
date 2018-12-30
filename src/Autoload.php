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

namespace Froq;

/**
 * @package Froq
 * @object  Froq\Autoload
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Autoload
{
    /**
     * Service names (default).
     * @const string
     */
    public const SERVICE_NAME_MAIN       = 'MainService',
                 SERVICE_NAME_FAIL       = 'FailService';

    /**
     * Namespaces.
     * @const string
     */
    public const NAMESPACE               = 'Froq',
                 NAMESPACE_APP_SERVICE   = 'Froq\\App\\Service',
                 NAMESPACE_APP_DATABASE  = 'Froq\\App\\Database',
                 NAMESPACE_APP_LIBRARY   = 'Froq\\App\\Library';

    /**
     * App dir.
     * @var string
     */
    private $appDir;

    /**
    * Singleton stuff.
    * @var self
    */
    private static $instance;

    /**
     * Constructor.
     * @throws \RuntimeException
     */
    private function __construct()
    {
        if (!defined('APP_DIR')) {
            throw new \RuntimeException('APP_DIR is not defined');
        }

        $this->appDir = APP_DIR;
    }

    /**
     * Destructor.
     * @return void
     */
    public function __destruct()
    {
        $this->unregister();
    }

    /**
     * Cloner.
     */
    private function __clone() {}

    /**
     * Init.
     * @return self
     */
    public static function init(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
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
     * @param  string $objectName
     * @return void
     * @throws \RuntimeException
     */
    public function load(string $objectName): void
    {
        // only Froq! stuff
        if (0 !== strpos($objectName, self::NAMESPACE)) {
            return;
        }

        $objectFile = $this->getObjectFile($objectName);

        if ($objectFile == null) {
            throw new \RuntimeException("Could not specify object file '{$objectName}'");
        }

        if (!file_exists($objectFile)) {
            throw new \RuntimeException("Could not find object file '{$objectFile}'");
        }

        require $objectFile;
    }

    /**
     * Get object file.
     * @param  string $objectName
     * @return ?string
     */
    public function getObjectFile(string $objectName): ?string
    {
        // user service objects
        if (0 === strpos($objectName, self::NAMESPACE_APP_SERVICE)) {
            $objectBase = $this->getObjectBase($objectName);
            if ($objectBase == self::SERVICE_NAME_MAIN || $objectBase == self::SERVICE_NAME_FAIL) {
                $objectFile = sprintf('%s/app/service/default/%s/%s.php',
                    $this->appDir, $objectBase, $objectBase);
            } else {
                $objectFile = sprintf('%s/app/service/%s/%s.php',
                    $this->appDir, $objectBase, $objectBase);
            }

            return $this->fixSlashes($objectFile);
        }

        // user model objects
        if (0 === strpos($objectName, self::NAMESPACE_APP_DATABASE) && 'Model' === substr($objectName, -5)) {
            $objectBase = $this->getObjectBase(substr($objectName, 0, -5 /* strlen('Model') */) . 'Service');
            if ($objectBase == self::SERVICE_NAME_MAIN || $objectBase == self::SERVICE_NAME_FAIL) {
                $objectFile = sprintf('%s/app/service/default/%s/model/model.php',
                    $this->appDir, $objectBase);
            } else {
                $objectFile = sprintf('%s/app/service/%s/model/model.php',
                    $this->appDir, $objectBase);
            }

            return $this->fixSlashes($objectFile);
        }

        // user library objects
        if (0 === strpos($objectName, self::NAMESPACE_APP_LIBRARY)) {
            return $this->fixSlashes(sprintf('%s/app/library/%s.php',
                $this->appDir, $this->getObjectBase($objectName, false)));
        }

        return null;
    }

    /**
     * Get object base.
     * @param  string $objectName
     * @param  bool   $endOnly
     * @return string
     */
    public function getObjectBase(string $objectName, bool $endOnly = true): string
    {
        $tmp = explode('\\', $objectName);
        $end = array_pop($tmp);

        if ($endOnly) {
            return $end;
        }

        // eg: Froq\App\Library\Entity\UserEntity => app/library/entity/UserEntity
        $path = strtolower(join('\\', array_slice($tmp, 3)));

        return $path .'\\'. $end;
    }

    /**
     * Fix slashes.
     * @param  string $path
     * @return string
     */
    public function fixSlashes($path): string
    {
        return str_replace('\\', '/', $path);
    }
}

// auto-init as a shorcut for require/include actions
return Autoload::init();
