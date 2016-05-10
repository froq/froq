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
class Autoload
{
    /**
     * Default service names.
     * @const string
     */
    const MAIN_SERVICE_NAME = 'MainService',
          FAIL_SERVICE_NAME = 'FailService';

    /**
    * Singleton stuff.
    * @var self
    */
    private static $instance;

    /**
    * Froq namespace.
    * @var string
    */
    private static $namespaces = [
        'Froq',
        'Froq\\App\\Service',
        'Froq\\App\\Library',
    ];

    /**
     * Forbid idle initializations.
     */
    final private function __clone() {}
    final private function __construct() {}

    /**
     * Destructor.
     * @return void
     */
    final public function __destruct()
    {
        $this->unregister();
    }

    /**
     * Init.
     * @return self
     */
    final public static function init(): self
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
    final public function register(): bool
    {
        return spl_autoload_register([$this, 'load']);
    }

    /**
     * Unregister.
     * @return bool
     */
    final public function unregister(): bool
    {
        return spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Load an object (class/trait/interface) file.
     * @param  string $objectName
     * @return any
     * @throws \RuntimeException
     */
    final public function load($objectName)
    {
        // only Froq! stuff
        if (0 !== strpos($objectName, self::$namespaces[0])) {
            return;
        }

        $objectFile = null;

        // user service objects
        if (0 === strpos($objectName, self::$namespaces[1])) {
            $objectBase =@ end(explode('\\', $objectName));
            $objectFile = ($objectBase == self::MAIN_SERVICE_NAME || $objectBase == self::FAIL_SERVICE_NAME)
                ? self::fixSlashes(sprintf('./app/service/default/%s/%s.php', $objectBase, $objectBase))
                : self::fixSlashes(sprintf('./app/service/%s/%s.php', $objectBase, $objectBase));
        }
        // user library objects
        elseif (0 === strpos($objectName, self::$namespaces[2])) {
            $objectBase =@ end(explode('\\', $objectName));
            $objectFile = self::fixSlashes(sprintf('./app/library/%s.php', $objectBase));
        }
        // Froq! objects
        elseif (0 === strpos($objectName, self::$namespaces[0])) {
            $objectFile = $this->fixSlashes(sprintf('%s/%s.php',
                __dir__, substr_replace($objectName, '', 0, strlen(self::$namespaces[0]))
            ));
        }

        if (!is_file($objectFile)) {
            throw new \RuntimeException("Object file not found! file: '{$objectFile}'.");
        }

        require($objectFile);
    }

    /**
     * Prepare file path.
     * @param  string $path
     * @return string
     */
    final private function fixSlashes($path): string
    {
        return preg_replace(['~\\\\~', '~/+~'], '/', $path);
    }
}

// auto-init as a shorcut for require/include actions
return Autoload::init();
