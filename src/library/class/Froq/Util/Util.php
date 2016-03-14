<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *   <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *   <http://www.gnu.org/licenses/gpl-3.0.txt>
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

namespace Froq\Util;

/**
 * @package    Froq
 * @subpackage Froq\Util
 * @object     Froq\Util\Util
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Util
{
   /**
    * Array pick.
    *
    * @param  array      $array
    * @param  string|int $key
    * @param  mixed      $value
    * @return mixed
    */
   final public static function arrayPick(array &$array, $key, $value = null)
   {
      if (isset($array[$key])) {
         $value = $array[$key];
         unset($array[$key]);
      }

      return $value;
   }

   /**
    * Array filter with key.
    *
    * @param  array         $array
    * @param  callable|null $callable
    * @return array
    */
   final public static function arrayFilter(array $array, callable $callable = null): array
   {
      if ($callable == null) {
         $callable = function($key, $value) {
            return ((bool) $value);
         };
      }

      $return = [];
      foreach ($array as $key => $value) {
         if ($callable($key, $value)) {
            $return[$key] = $value;
         }
      }

      return $return;
   }

   /**
    * Array exclude.
    *
    * @param  array  $array
    * @param  array  $excludeKeys
    * @return array
    */
   final public static function arrayExclude(array $array, array $excludeKeys): array
   {
      $return = [];
      foreach ($array as $key => $value) {
         if (!in_array($key, $excludeKeys)) {
            $return[$key] = $value;
         }
      }

      return $return;
   }
}
