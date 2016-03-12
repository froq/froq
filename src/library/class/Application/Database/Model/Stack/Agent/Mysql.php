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

namespace Application\Database\Model\Stack\Agent;

use Application\Util\Util;
use Application\Database\Model\Stack\Stack;
use Application\Database\Vendor\Vendor as Database;
use Oppa\Database\Query\Builder as QueryBuilder;

/**
 * @package    Application
 * @subpackage Application\Database\Model\Stack\Agent
 * @object     Application\Database\Model\Stack\Agent\Mysql
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
    * @param Application\Database\Vendor\Vendor $db
    * @param string                             $name
    * @param string                             $primary
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
    * @param  mixed $primaryValue
    * @return stcClass|null
    */
   final public function find($primaryValue = null)
   {
      if ($primaryValue === null) {
         $primaryValue = dig($this->data, $this->primary);
      }

      if ($primaryValue === null) {
         return;
      }

      try {
         return $this->db->getConnection()->getAgent()->get(
            "SELECT * FROM `{$this->name}` WHERE `{$this->primary}` = ?", [$primaryValue]);
      } catch (\Throwable $e) {
         $this->fail = $e;
      }
   }

   /**
    * Find all object.
    *
    * @param  string    $where
    * @param  array     $params
    * @param  int|array $limit
    * @param  int       $order
    * @return array|null
    */
   final public function findAll(string $where = null, array $params = null, $limit = null,
      int $order = -1)
   {
      try {
         $agent = $this->db->getConnection()->getAgent();

         $query = empty($where)
            ? sprintf('SELECT * FROM `%s`', $this->name)
            : sprintf('SELECT * FROM `%s` WHERE (%s)', $this->name, $where);

         $query = (($order == -1)
            ? sprintf('%s ORDER BY `%s` DESC ', $query, $this->primary)
            : sprintf('%s ORDER BY `%s` ASC ', $query, $this->primary)
         ) . $agent->limit($limit ?: self::SELECT_LIMIT);

         return $agent->getAll($query, $params);
      } catch (\Throwable $e) {
         $this->fail = $e;
      }
   }

   /**
    * Save an object.
    *
    * @return int|null
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
            $query->update($this->data)->whereEqual(
               $agent->escapeIdentifier($this->primary), $id)->limit(1)->toString();
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
            $return = (!$id) ? $result->getId() : $result->getRowsAffected();
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
    * @return int|null
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
         $query->delete()->whereEqual(
            $agent->escapeIdentifier($this->primary), $id)->limit(1)->toString();

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
