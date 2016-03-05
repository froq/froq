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

namespace Application\Util;

use Application\Application;

/**
 * @package    Application
 * @subpackage Application\Util
 * @object     Application\Util\View
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class View
{
   /**
    * Partial files.
    * @const string
    */
   const PARTIAL_HEAD = 'partial/head',
         PARTIAL_FOOT = 'partial/foot';

   /**
    * Asset pre/post directions.
    * @const string, string
    */
   const ASSET_DIR_PRE  = 'pre',
         ASSET_DIR_POST = 'post';

   /**
    * Application object.
    * @var Application\Application
    */
   private $app;

   /**
    * Inline pre/post styles.
    * @var array
    */
   private $assetStyles = [];

   /**
    * Constructor.
    *
    * @param Application\Application $app
    */
   final public function __construct(Application $app)
   {
      $this->app = $app;
   }

   /**
    * Render view file.
    *
    * @param  string $file
    * @param  array  $data
    * @return void
    */
   final public function display(string $file, array $data = null)
   {
      $this->includeFile($this->prepareFile($file), $data);

      // add pre/post styles
      if (isset($this->assetStyles[self::ASSET_DIR_PRE])) {
         $stylePrepend = '';
         foreach ($this->assetStyles[self::ASSET_DIR_PRE] as $style) {
            $stylePrepend .= "<style>{$style}</style>\n";
         }
         set_global('style.prepend', trim($stylePrepend));
      }
      if (isset($this->assetStyles[self::ASSET_DIR_POST])) {
         $styleAppend = '';
         foreach ($this->assetStyles[self::ASSET_DIR_POST] as $style) {
            $styleAppend .= "<style>{$style}</style>\n";
         }
         set_global('style.append', trim($styleAppend));
      }
   }

   /**
    * Render partial/header file.
    *
    * @param  array $data
    * @return void
    */
   final public function displayHead(array $data = null)
   {
      // check local service file
      $file = $this->prepareFile(self::PARTIAL_HEAD, false);
      if (!is_file($file)) {
         // look up for global service file
         $file = $this->prepareFileGlobal(self::PARTIAL_HEAD);
      }

      $this->includeFile($file, $data);
   }

   /**
    * Render partial/footer file.
    *
    * @param  array $data
    * @return void
    */
   final public function displayFoot(array $data = null)
   {
      // check local service file
      $file = $this->prepareFile(self::PARTIAL_FOOT, false);
      if (!is_file($file)) {
         // look up for global service file
         $file = $this->prepareFileGlobal(self::PARTIAL_FOOT);
      }

      $this->includeFile($file, $data);
   }

   /**
    * Include file.
    *
    * @param  string $file
    * @param  array  $data
    * @return void
    */
   final public function includeFile(string $file, array $data = null)
   {
      if (!empty($data)) {
         extract($data);
      }

      include($file);
   }

   /**
    * Prepare file path.
    *
    * @param  string $file
    * @param  bool   $fileCheck
    * @return string
    */
   final public function prepareFile(string $file, bool $fileCheck = true): string
   {
      // default file given
      if ($file[0] == '.') {
         $file = sprintf('%s.php', $file);
      } else {
         $file = sprintf('./app/service/%s/view/%s.php',
            $this->app->service->name, $file);
      }

      if ($fileCheck && !is_file($file)) {
         throw new \RuntimeException('View file not found! file: '. $file);
      }

      return $file;
   }

   /**
    * Prepare global file path.
    *
    * @param  string $file
    * @param  bool   $fileCheck
    * @return string
    */
   final public function prepareFileGlobal(string $file, bool $fileCheck = true): string
   {
      $file = sprintf('./app/service/view/%s.php', $file);
      if ($fileCheck && !is_file($file)) {
         throw new \RuntimeException('View file not found! file: '. $file);
      }

      return $file;
   }

   /**
    * Add inline style tag.
    *
    * @param string $style
    * @param string $dir
    */
   final public function addStyle(string $style, string $dir = self::ASSET_DIR_PRE): self
   {
      $this->assetStyles[$dir][] = $style;

      return $this;
   }

   /**
    * Add inline style tag after body.
    *
    * @param string $style
    */
   final public function addStylePre(string $style): self
   {
      return $this->addStyle($style, self::ASSET_DIR_PRE);
   }

   /**
    * Add inline style tag before body.
    *
    * @param string $style
    */
   final public function addStylePost(string $style): self
   {
      return $this->addStyle($style, self::ASSET_DIR_POST);
   }
}
