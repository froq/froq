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

namespace Froq\Http\Request;

use Froq\Util\Traits\GetterTrait as Getter;
use Froq\Http\Request\Params\{Get, Post, Cookie};

/**
 * @package    Froq
 * @subpackage Froq\Http\Request
 * @object     Froq\Http\Request\Params
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Params
{
   /**
    * Getter.
    * @object Froq\Util\Traits\GetterTrait
    */
   use Getter;

   /**
    * Get params.
    * @var Froq\Http\Request\Params\Get
    */
   private $get;

   /**
    * Post params.
    * @var Froq\Http\Request\Params\Post
    */
   private $post;

   /**
    * Cookie params.
    * @var Froq\Http\Request\Params\Cookie
    */
   private $cookie;

   /**
    * Constructor.
    */
   final public function __construct()
   {
      $this->get = new Get();
      $this->post = new Post();
      $this->cookie = new Cookie();
   }

   /**
    * Get a GET param.
    *
    * @param  string $key
    * @param  mixed  $valueDefault
    * @return mixed
    */
   final public function get(string $key, $valueDefault = null)
   {
      return $this->get->get($key, $valueDefault);
   }

   /**
    * Get a POST param.
    *
    * @param  string $key
    * @param  mixed  $valueDefault
    * @return mixed
    */
   final public function post(string $key, $valueDefault = null)
   {
      return $this->post->get($key, $valueDefault);
   }

   /**
    * Get a COOKIE param.
    *
    * @param  string $key
    * @param  mixed  $valueDefault
    * @return mixed
    */
   final public function cookie(string $key, $valueDefault = null)
   {
      return $this->cookie->get($key, $valueDefault);
   }
}
