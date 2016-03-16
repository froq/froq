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

namespace Froq\Http;

use Froq\Util\Collection;

/**
 * @package    Froq
 * @subpackage Froq\Http
 * @object     Froq\Http\Headers
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Headers extends Collection
{
   /**
    * Constructor.
    *
    * @param array $headers
    */
   final public function __construct(array $headers = [])
   {
      parent::__construct($headers);
   }

   /**
    * @overwride
    * @param  int|string $key
    * @param  any        $valueDefault
    * @return any
    */
   final public function get($key, $valueDefault = null)
   {
      // try with modified key
      if (!$this->offsetExists($key)) {
         $key = to_dash_snakecase($key);
      }

      return parent::get($key, $valueDefault);
   }
}
