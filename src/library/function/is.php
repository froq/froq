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

/*** "IS" function module. ***/

/**
 * Check env is local.
 * @return bool
 */
function is_local(): bool
{
   if (defined('local')) {
      return (local === true);
   }
   return ((bool) strstr($_SERVER['SERVER_NAME'], '.local'));
}

/**
 * Check input is in array.
 * @param  array        $array
 * @param  string|array $input
 * @return bool
 */
function is_in(array $array, $input): bool
{
   if (is_array($input)) {
      foreach ($input as $input) {
         if (in_array($input, $array)) {
            return true;
         }
      }
      return false;
   }

   return in_array($input, $array);
}

/**
 * Check input is valid uint ID.
 * @param  any $input
 * @return bool
 */
function is_id($input): bool
{
   return (($input = intval($input)) && $input > 0);
}

/**
 * Check callee allowed.
 * @param  string      $filePath
 * @param  array|null  &$callee
 * @param  string|null &$error
 * @return bool
 */
function is_callee_allowed(string $filePath, array &$callee = null, string &$error = null): bool
{
   $callee = get_callee(4);
   if (strpos($callee['file'], $filePath)) {
      $error = sprintf('Call from bad scope! class: %s::%s() file: %s:%d',
         $callee['class'], $callee['function'], $callee['file'], $callee['line']);
      return false;
   }

   return true;
}

/**
 * Check var is iterable.
 * @param  any $input
 * @return bool
 */
function is_iter($input): bool
{
   return is_array($input)
      || ($input instanceof \stdClass)
      || ($input instanceof \Traversable);
}

/**
 * Check empty vars.
 * @param  array ...$inputs
 * @return bool
 */
function is_empty(...$inputs): bool
{
   foreach ($inputs as $input) {
      if (empty($input)) {
         return true;
      }
      if (is_object($input) && empty((array) $input)) {
         return true;
      }
   }

   return false;
}
