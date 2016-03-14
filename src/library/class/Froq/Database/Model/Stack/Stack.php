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

namespace Froq\Database\Model\Stack;

use Froq\Util\Traits\GetterTrait as Getter;

/**
 * @package    Froq
 * @subpackage Froq\Database\Model\Stack
 * @object     Froq\Database\Model\Stack\Stack
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Stack implements StackInterface
{
   /**
    * Getter.
    * @object Froq\Util\Traits\GetterTrait
    */
   use Getter;

   /**
    * Limits.
    * @const int
    */
   const SELECT_LIMIT = 10,
         UPDATE_LIMIT = 1,
         DELETE_LIMIT = 1;

   /**
    * Database object.
    * @var Froq\Database\Vendor\Vendor
    */
   protected $db;

   /**
    * Stack name.
    * @var string
    */
   protected $name;

   /**
    * Stack primary.
    * @var string
    */
   protected $primary;

   /**
    * Stack data.
    * @var array
    */
   protected $data = [];

   /**
    * Exception object that could be any type of.
    * @var \Throwable
    */
   protected $fail;

   /**
    * Set a field value.
    *
    * @param string $key
    * @param mixed  $value
    */
   final public function set(string $key, $value): self
   {
      $this->data[$key] = $value;

      return $this;
   }

   /**
    * Get a field value.
    *
    * @param  string $key
    * @return mixed
    */
   final public function get(string $key)
   {
      // return all
      if ($key == '*') {
         return $this->data;
      }

      if (array_key_exists($key, $this->data)) {
         return $this->data[$key];
      }

      return null;
   }

   /**
    * Check a field.
    *
    * @param  string $key
    * @return bool
    */
   final public function isset(string $key): bool
   {
      return array_key_exists($key, $this->data);
   }

   /**
    * Unset a field value.
    *
    * @param  string $key
    * @return void
    */
   final public function unset(string $key)
   {
      unset($this->data[$key]);
   }

   /**
    * Get db.
    *
    * @return string
    */
   final public function getDb(): string
   {
      return $this->db;
   }

   /**
    * Get name.
    *
    * @return string
    */
   final public function getName(): string
   {
      return $this->name;
   }

   /**
    * Get primary.
    *
    * @return string
    */
   final public function getPrimary(): string
   {
      return $this->primary;
   }

   /**
    * Get primary value.
    *
    * @return mixed
    */
   final public function getPrimaryValue()
   {
      return $this->data[$this->primary] ?? null;
   }

   /**
    * Set data.
    *
    * @param  array $data
    * @return self
    */
   final public function setData(array $data): self
   {
      $this->data = $data;

      return $this;
   }

   /**
    * Get data.
    *
    * @param  string|array $exclude
    * @return array
    */
   final public function getData(): array
   {
      return $this->data;
   }

   /**
    * Get data value.
    *
    * @param  string $key
    * @return mixed
    */
   final public function getDataValue(string $key)
   {
      return $this->data[$key] ?? null;
   }

   /**
    * Get data keys.
    *
    * @return array
    */
   final public function getDataKeys(): array
   {
      $keys = [];
      if (!empty($this->data)) {
         $keys = array_keys($this->data);
      }

      return $keys;
   }

   /**
    * Get data values.
    *
    * @return array
    */
   final public function getDataValues(): array
   {
      $values = [];
      if (!empty($this->data)) {
         $values = array_values($this->data);
      }

      return $values;
   }

   /**
    * Get fail.
    *
    * @return \Throwable|null
    */
   final public function getFail()
   {
      return $this->fail;
   }
}
