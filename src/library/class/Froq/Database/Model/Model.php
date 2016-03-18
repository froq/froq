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

namespace Froq\Database\Model;

use Froq\Database\Database;
use Froq\Database\Vendor\Vendor;
use Froq\Database\Model\Stack\Stack;
use Froq\Database\Model\Stack\Agent\Mysql;
// @todo
// use Froq\Database\Model\Stack\Agent\Couch;
// use Froq\Database\Model\Stack\Agent\Mongo;

/**
 * @package    Froq
 * @subpackage Froq\Database\Model
 * @object     Froq\Database\Model\Model
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Model
{
   /**
    * Vendor.
    * @var string
    */
   protected $vendor;

   /**
    * Stack agent.
    * @var Froq\Database\Model\Stack\Stack,
    */
   protected $stack;

   /**
    * Stack name (table, collection, etc.).
    * @var string
    */
   protected $stackName;

   /**
    * Stack primary (key).
    * @var string
    */
   protected $stackPrimary;

   /**
    * Use transaction?
    * @var bool
    */
   protected $useTransaction = true;

   /**
    * Constructor.
    *
    * @throws \Exception
    */
   final public function __construct()
   {
      switch ($this->vendor) {
         // only mysql for now
         case Database::VENDOR_MYSQL:
            $this->stack = new Mysql(
               Database::init(Database::VENDOR_MYSQL),
               $this->stackName,
               $this->stackPrimary,
               $this->useTransaction
            );
            break;
         default:
            throw new \Exception('Unimplemented vendor given!');
      }

      // copy public vars as stack data
      foreach (array_diff(
         array_keys(get_object_vars($this)),
         array_keys(get_class_vars(__class__))) as $var) {
         $this->stack->set($var, $this->{$var});
         unset($this->{$var});
      }
   }

   /**
    * Proxy method for stack object methods.
    *
    * @param  string $method
    * @param  array  $arguments
    * @return mixed
    * @throws \BadMethodCallException
    */
   final public function __call($method, array $arguments)
   {
      if (method_exists($this->stack, $method)) {
         return call_user_func_array([$this->stack, $method], $arguments);
      }

      throw new \BadMethodCallException("Call to undefined method `{$method}`!");
   }

   /**
    * Proxy method for stack::set.
    *
    * @param  string $key
    * @param  mixed  $value
    * @return Froq\Database\Model\Stack\Stack
    */
   final public function __set(string $key, $value)
   {
      return $this->stack->set($key, $value);
   }

   /**
    * Proxy method for stack::get.
    *
    * @param  string $key
    * @return mixed
    */
   final public function __get(string $key)
   {
      return $this->stack->get($key);
   }

   /**
    * Proxy method for stack::isset.
    *
    * @param  string $key
    * @return bool
    */
   final public function __isset(string $key): bool
   {
      return $this->stack->isset($key);
   }

   /**
    * Proxy method for stack::unset.
    *
    * @param  string $key
    * @return void
    */
   final public function __unset(string $key)
   {
      return $this->stack->unset($key);
   }

   /**
    * Get verdor name.
    *
    * @return string
    */
   final public function getVendor(): string
   {
      return $this->vendor;
   }

   /**
    * Get stack object.
    *
    * @return Froq\Database\Model\Stack\Stack
    */
   final public function getStack(): Stack
   {
      return $this->stack;
   }

   /**
    * Get stack database object.
    *
    * @return Froq\Database\Vendor\Vendor
    */
   final public function getStackDb(): Vendor
   {
      return $this->stack->getDb();
   }

   /**
    * Get stack name.
    *
    * @return string
    */
   final public function getStackName(): string
   {
      return $this->stack->getName();
   }

   /**
    * Get stack primary.
    *
    * @return string
    */
   final public function getStackPrimary(): string
   {
      return $this->stack->getPrimary();
   }

   /**
    * Load field values.
    *
    * @param  array  $data
    * @return self
    */
   final public function load(array $data): self
   {
      foreach ($data as $key => $value) {
         $this->stack->set($key, $value);
      }

      return $this;
   }

   /**
    * Unload field values.
    *
    * @return self
    */
   final public function unload(): self
   {
      foreach ($this->stack->data as $key => $value) {
         $this->stack->unset($key);
      }

      return $this;
   }
}
