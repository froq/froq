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

use Froq\App;

/**
 * @package    Froq
 * @subpackage Froq\Util
 * @object     Froq\Util\View
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
    * App object.
    * @var Froq\App
    */
   private $app;

   /**
    * View metas.
    *    Available names to replace while sending HTML output are;
    *    page.title, page.title.pre, page.title.post, page.description
    * @var array
    */
   private $metas = [];

   /**
    * Inline pre/post styles.
    * @var array
    */
   private $assetStyles = [];

   /**
    * Inline pre/post scripts.
    * @var array
    */
   private $assetScripts = [];

   /**
    * Constructor.
    *
    * @param Froq\App $app
    */
   final public function __construct(App $app)
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

      // make global metas
      if (!empty($this->metas)) {
         foreach ($this->metas as $name => $value) {
            set_global($name, $value);
         }
      }

      // short-ref
      $dirPre = self::ASSET_DIR_PRE;
      $dirPost = self::ASSET_DIR_POST;

      // make global pre styles
      if (isset($this->assetStyles[$dirPre])) {
         $stylePrepend = '';
         foreach ($this->assetStyles[$dirPre] as $style) {
            $stylePrepend .= sprintf("<style>%s</style>\n", $style);
         }
         set_global('style.prepend', trim($stylePrepend));
      }

      // make global post styles
      if (isset($this->assetStyles[$dirPost])) {
         $styleAppend = '';
         foreach ($this->assetStyles[$dirPost] as $style) {
            $styleAppend .= sprintf("<style>%s</style>\n", $style);
         }
         set_global('style.append', trim($styleAppend));
      }

      // make global pre scripts
      if (isset($this->assetScripts[$dirPre])) {
         $scriptPrepend = '';
         foreach ($this->assetScripts[$dirPre] as $script) {
            $scriptPrepend .= sprintf("<script>%s</script>\n", $script);
         }
         set_global('script.prepend', trim($scriptPrepend));
      }

      // make global post scripts
      if (isset($this->assetScripts[$dirPost])) {
         $scriptAppend = '';
         foreach ($this->assetScripts[$dirPost] as $script) {
            $scriptAppend .= sprintf('<script>%s</script>\n', $script);
         }
         set_global('script.append', trim($scriptAppend));
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

      // check file
      if ($fileCheck && !is_file($file)) {
         // look up default folder
         if ($this->app->service->isDefault()) {
            $file = sprintf('./app/service/default/%s/view/%s',
               $this->app->service->name, basename($file));
         }

         if (!is_file($file)) {
            throw new \RuntimeException('View file not found! file: '. $file);
         }
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
    * Add meta.
    *
    * @param string $name
    * @param string $value
    */
   final public function setMeta(string $name, string $value): self
   {
      $this->metas[$name] = $value;

      return $this;
   }

   /**
    * Get meta.
    *
    * @param  string $name
    * @param  string $valueDefault
    * @return string
    */
   final public function getMeta(string $name, string $valueDefault = ''): string
   {
      return $this->metas[$name] ?? $valueDefault;
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

   /**
    * Add inline script tag.
    *
    * @param string $script
    * @param string $dir
    */
   final public function addScript(string $script, string $dir = self::ASSET_DIR_PRE): self
   {
      $this->assetScripts[$dir][] = $script;

      return $this;
   }

   /**
    * Add inline script tag after body.
    *
    * @param string $script
    */
   final public function addScriptPre(string $script): self
   {
      return $this->addScript($script, self::ASSET_DIR_PRE);
   }

   /**
    * Add inline script tag before body.
    *
    * @param string $script
    */
   final public function addScriptPost(string $script): self
   {
      return $this->addScript($script, self::ASSET_DIR_POST);
   }
}
