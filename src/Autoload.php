<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *     <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *     <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
     */
    private function __construct()
    {
        if (!defined('APP_DIR')) {
            throw new RuntimeException('APP_DIR is not defined!');
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
    public function load($objectName): void
    {
        // only Froq! stuff
        if (0 !== strpos($objectName, self::NAMESPACE)) {
            return;
        }

        $objectFile = $this->getObjectFile($objectName);

        if ($objectFile == null) {
            throw new \RuntimeException("Could not specify object file '{$objectName}'!");
        }
        if (!is_file($objectFile)) {
            throw new \RuntimeException("Could not found object file '{$objectFile}'!");
        }

        require($objectFile);
    }

    /**
     * Get object file.
     * @param  string $objectName
     * @return ?string
     */
    public function getObjectFile(string $objectName): ?string
    {
        // user service & model objects
        if (0 === strpos($objectName, self::NAMESPACE_APP_SERVICE)) {
            // model object
            if (preg_match('~Service\\\(\w+)Model$~i', $objectName, $match)) {
                $objectBase = ucfirst($match[1] .'Service');
                if ($objectBase == self::SERVICE_NAME_MAIN || $objectBase == self::SERVICE_NAME_FAIL) {
                    $objectFile = sprintf('%s/app/service/default/%s/model/model.php',
                        $this->appDir, $objectBase);
                } else {
                    $objectFile = sprintf('%s/app/service/%s/model/model.php',
                        $this->appDir, $objectBase);
                }
            }
            // service object
            else {
                $objectBase = $this->getObjectBase($objectName);
                if ($objectBase == self::SERVICE_NAME_MAIN || $objectBase == self::SERVICE_NAME_FAIL) {
                    $objectFile = sprintf('%s/app/service/default/%s/%s.php',
                        $this->appDir, $objectBase, $objectBase);
                } else {
                    $objectFile = sprintf('%s/app/service/%s/%s.php',
                        $this->appDir, $objectBase, $objectBase);
                }
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

        // eg: Froq\App\Library\Entity\UserEntity => ./app/library/entity/UserEntity
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
        return preg_replace(['~\\\~', '~/+~'], '/', $path);
    }
}

// auto-init as a shorcut for require/include actions
return Autoload::init();
