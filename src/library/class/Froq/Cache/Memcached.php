<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
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

namespace Froq\Cache;

/**
 * @package    Froq
 * @subpackage Froq\Cache
 * @object     Froq\Cache\Memcached
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Memcached extends Cache implements CacheInterface
{
   /**
    * Default host.
    * @const string
    */
   const DEFAULT_HOST = 'localhost';

   /**
    * Default port.
    * @const int
    */
   const DEFAULT_PORT = 11211;

   /**
    * Constructor.
    *
    * @param string $id
    * @param string $host
    * @param int    $port
    */
   final private function __construct(string $id = null,
      string $host = self::DEFAULT_HOST, int $port = self::DEFAULT_PORT)
   {
      $this->id   = $id;
      $this->host = $host;
      $this->port = $port;

      if (empty($this->id)) {
         $this->client = new \Memcached();
         $this->client->addServer($host, $port);
      } else {
         $this->client = new \Memcached($this->id);
         $this->client->addServer($host, $port);
      }
   }

   /**
    * Get client instance.
    *
    * @param  string $id
    * @param  string $host
    * @param  int    $port
    * @return Froq\Cache\CacheInterface
    */
   final public static function getClientInstance(string $id = null,
      string $host = self::DEFAULT_HOST, int $port = self::DEFAULT_PORT): CacheInterface
   {
      // not persistent
      if (empty($id)) {
         $key = sprintf('%s:%s', $host, $port);
         if (!isset(self::$clients[$key])) {
            self::$clients[$key] = new self(null, $host, $port);
         }
      }
      // persistent
      else {
         $key = sprintf('%s:%s-%s', $host, $port, $id);
         if (!isset(self::$clients[$key])) {
            self::$clients[$key] = new self($id, $host, $port);
         }
      }

      return self::$clients[$key];
   }

   /**
    * Set cache item.
    *
    * @param  string   $key
    * @param  mixed    $value
    * @param  int|null $expiration
    * @return bool
    */
   final public function set(string $key, $value, int $expiration = 0): bool
   {
      return $this->client->set($key, $value, $expiration);
   }

   /**
    * Get cache item.
    *
    * @param  string $key
    * @return mixed|null
    */
   final public function get(string $key)
   {
      return $this->client->get($key);
   }

   /**
    * Delete cache item.
    *
    * @param  string $key
    * @return bool
    */
   final public function delete(string $key): bool
   {
      return $this->client->delete($key);
   }
}
