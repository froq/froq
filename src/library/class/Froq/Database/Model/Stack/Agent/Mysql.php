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

namespace Froq\Database\Model\Stack\Agent;

use Froq\Util\Util;
use Froq\Database\Model\Stack\Stack;
use Froq\Database\Vendor\Vendor as Database;
use Oppa\Database\Query\Builder as QueryBuilder;

/**
 * @package    Froq
 * @subpackage Froq\Database\Model\Stack\Agent
 * @object     Froq\Database\Model\Stack\Agent\Mysql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Stack
{
   /**
    * Use transaction?
    * @var bool
    */
   protected $useTransaction = true;

   /**
    * Constructor.
    *
    * @param Froq\Database\Vendor\Vendor $db
    * @param string                      $name
    * @param string                      $primary
    */
   final public function __construct(Database $db, $name, $primary, bool $useTransaction = true)
   {
      $this->db = $db;
      $this->name = trim($name);
      $this->primary = trim($primary);
      $this->useTransaction = $useTransaction;
   }

   /**
    * Find an object.
    *
    * @param  any $id
    * @return stcClass|null
    */
   final public function find($id = null)
   {
      $id = $id ?? $this->getPrimaryValue();
      if ($id === null) {
         return;
      }

      try {
         return $this->createQueryBuilder()
            ->select('*')->whereEqual("`{$this->primary}`", $id)->limit(1)
            ->get();
      } catch (\Throwable $e) {
         // set exception
         $this->fail = $e;
      }
   }

   /**
    * Find objects.
    *
    * @param  string    $where
    * @param  array     $params
    * @param  int|array $limit
    * @param  int       $order
    * @return array|null
    */
   final public function findAll(string $where = null, array $params = null,
      $limit = self::SELECT_LIMIT, int $order = -1)
   {
      try {
         $query = $this->createQueryBuilder();
         $query->select('*');
         // where
         if (!empty($where)) {
            $query->where($where, $params);
         }
         // order
         if ($order == -1) {
            $query->orderBy("`{$this->primary}`", QueryBuilder::OP_DESC);
         } elseif ($order == 1) {
            $query->orderBy("`{$this->primary}`", QueryBuilder::OP_ASC);
         }
         // limit
         $query->limit($limit);

         return $query->getAll();
      } catch (\Throwable $e) {
         // set exception
         $this->fail = $e;
      }
   }

   /**
    * Save an object.
    *
    * @return int|bool|null
    */
   final public function save()
   {
      $agent = $this->db->getConnection()->getAgent();
      $batch = null;
      if ($this->useTransaction) {
         $batch = $agent->getBatch();
         // set autocommit=0
         $batch->lock();
      }

      // create query builder
      $query = $this->createQueryBuilder();

      $return = null;
      try {
         $id = $this->getPrimaryValue();
         if (!$id) { // insert action
            $query->insert($this->data)->toString();
         } else {    // update action
            $query->update($this->data)->whereEqual("`{$this->primary}`", $id)
               ->limit(self::UPDATE_LIMIT)->toString();
         }

         if ($this->useTransaction) {
            $batch->queue($query);
            $batch->run();
            $result = $batch->getResult()[0] ?? null;
         } else {
            $result = $agent->query($query);
         }

         // set return
         if ($result !== null) {
            if (!$id) {
               $return = $result->getId();
               // set new id
               $this->setPrimaryValue($return);
            } else {
               $return = $result->getRowsAffected();
            }
         }

         // updated but no rows affected?
         if ($id && ($return === 0 || $result === 0 || $result === null)) {
            $return = true;
         }
      } catch (\Throwable $e) {
         // set exception
         $this->fail = $e;

         // rollback & set autocommit=1
         $batch && $batch->cancel();
      }

      // set autocommit=1
      $batch && $batch->unlock();

      return $return;
   }

   /**
    * Remove an object.
    *
    * @return bool
    */
   final public function remove(): bool
   {
      $id = $this->getPrimaryValue();
      if (!$id) {
         return false;
      }

      $agent = $this->db->getConnection()->getAgent();
      $batch = null;
      if ($this->useTransaction) {
         $batch = $agent->getBatch();
         // set autocommit=0
         $batch->lock();
      }

      // create query builder
      $query = $this->createQueryBuilder();

      $return = false;
      try {
         $query->delete()->whereEqual("`{$this->primary}`", $id)
            ->limit(self::DELETE_LIMIT)->toString();

         if ($this->useTransaction) {
            $batch->queue($query);
            $batch->run();
            $result = $batch->getResult()[0] ?? null;
         } else {
            $result = $agent->query($query);
         }

         // set return
         if ($result !== null) {
            $return = (bool) $result->getRowsAffected();
         }
      } catch (\Throwable $e) {
         // set exception
         $this->fail = $e;

         // rollback & set autocommit=1
         $batch && $batch->cancel();
      }

      // set autocommit=1
      $batch && $batch->unlock();

      return $return;
   }

   /**
    * Count objects.
    *
    * @param  string|null $where
    * @param  array|null  $params
    * @return int
    */
   final public function count(string $where = null, array $params = null): int
   {
      try {
         $query = $this->createQueryBuilder();
         // where
         if (!empty($where)) {
            $query->where($where, $params);
         }

         return $query->count();
      } catch (\Throwable $e) {
         // set exception
         $this->fail = $e;

         return -1;
      }
   }

   /**
    * Create a fresh query builder.
    *
    * @return Oppa\Database\Query\Builder
    */
   final public function createQueryBuilder(): QueryBuilder
   {
      return new QueryBuilder(
         $this->db->getConnection(),
         $this->db->getConnection()->getAgent()->escapeIdentifier($this->name)
      );
   }
}
