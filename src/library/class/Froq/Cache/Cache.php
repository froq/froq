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
 * @object     Froq\Cache\Cache
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Cache
{
   /**
    * Client ID.
    * @var string
    */
   protected $id;

   /**
    * Host.
    * @var string
    */
   protected $host;

   /**
    * Port.
    * @var int
    */
   protected $port;

   /**
    * Client.
    * @var mixed
    */
   protected $client;

   /**
    * Client array.
    * @var array
    */
   protected static $clients = [];

   /**
    * Get id.
    *
    * @return string|null
    */
   final public function getId()
   {
      return $this->id;
   }

   /**
    * Get host.
    *
    * @return string|null
    */
   final public function getHost()
   {
      return $this->host;
   }

   /**
    * Get port.
    *
    * @return int|null
    */
   final public function getPort()
   {
      return $this->port;
   }

   /**
    * Get client.
    *
    * @return mixed|null
    */
   final public function getClient()
   {
      return $this->client;
   }

   /**
    * Get clients.
    *
    * @return array
    */
   final public function getClients(): array
   {
      return self::$clients;
   }

   /**
    * Get client instance.
    *
    * @param  string $id
    * @param  string $host
    * @param  int    $port
    * @return Froq\Cache\CacheInterface
    */
   abstract public static function getClientInstance(string $id = null,
      string $host = null, int $port = null): CacheInterface;
}
