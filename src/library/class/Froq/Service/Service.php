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

namespace Froq\Service;

use Froq\App;
use Froq\Util\{View, Config};
use Froq\Util\Traits\GetterTrait as Getter;
use Froq\Validation\{Validation, ValidationRule, ValidationException};

/**
 * @package    Froq
 * @subpackage Froq\Service
 * @object     Froq\Service\Service
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Service implements ServiceInterface
{
   /**
    * Getter.
    * @object Froq\Util\Traits\Getter
    */
   use Getter;

   /**
    * Froq object.
    * @var Froq\App
    */
   protected $app;

   /**
    * Service name.
    * @var string
    */
   protected $name;

   /**
    * Service method.
    * @var string
    */
   protected $method;

   /**
    * Service method args.
    * @var array
    */
   protected $methodArgs = [];

   /**
    * Service model.
    * @var Froq\Database\Model\Model
    */
   protected $model;

   /**
    * Service view.
    * @var Froq\Util\View
    */
   protected $view;

   /**
    * Service config.
    * @var Froq\Util\Config
    */
   protected $config;

   /**
    * Call only main() method.
    * @var bool
    */
   protected $useMainOnly = false;

   /**
    * Use both header/footer files.
    * @var bool
    */
   protected $useViewPartialAll  = false;

   /**
    * Use header file.
    * @var bool
    */
   protected $useViewPartialHead = false;

   /**
    * Use footer file.
    * @var bool
    */
   protected $useViewPartialFoot = false;

   /**
    * Validation.
    * @var Froq\Validation\Validation
    */
   protected $validation;

   /**
    * Validation rules (that could be overwriten calling validation::setRules() method after.)
    * @var array
    */
   protected $validationRules = [];

   /**
    * Request method limiter.
    * @var array
    */
   protected $allowedRequestMethods = [];

   /**
    * Constructor.
    *
    * @param Froq\App $app
    * @param string   $name
    * @param string   $method
    */
   final public function __construct(App $app, string $name = null, string $method = null)
   {
      $this->app = $app;

      $this->setName($name);
      $this->setMethod($method);
      $this->setMethodArgs(null);

      // load config & model
      $this->loadConfig();
      $this->loadModel();

      // create view
      $this->view = new View($this->app);

      // create validation
      if (empty($this->validationRules) && $this->config != null) {
         $this->validationRules = $this->config->get('validation.rules', []);
      }
      $this->validation = new Validation($this->validationRules);

      // prevent lowercased method names
      if (!empty($this->allowedRequestMethods)) {
         $this->allowedRequestMethods = array_map('strtoupper', $this->allowedRequestMethods);
      }
   }

   /**
    * Set name.
    *
    * @param  string $name
    * @return self
    */
   final public function setName(string $name = null): self
   {
      $this->name = (string) $name;

      return $this;
   }

   /**
    * Set method.
    *
    * @param  string $method
    * @return self
    */
   final public function setMethod(string $method = null): self
   {
      $this->method = (string) $method;

      return $this;
   }

   /**
    * Set method args.
    *
    * @param  $methodArgs
    * @return self
    */
   final public function setMethodArgs(array $methodArgs = null): self
   {
      if ($methodArgs === null) {
         $methodArgs = array_slice($this->app->request->uri->segments(), 2);
      }

      $this->methodArgs = $methodArgs;

      return $this;
   }

   /**
    * Check is main.
    *
    * @return bool
    */
   final public function isMain(): bool
   {
      return empty($this->method)
         || ($this->method == ServiceInterface::METHOD_MAIN);
   }

   /**
    * Run so call called method, service related methods
    * and return target method's return.
    *
    * @return string|null
    */
   final public function run()
   {
      // init method
      if (method_exists($this, ServiceInterface::METHOD_INIT)) {
         $this->{ServiceInterface::METHOD_INIT}();
      }

      // handlers
      $appOnBefore = $this->app->getHandler('onbefore');
      $appOnAfter  = $this->app->getHandler('onafter');

      // call app's onbefore
      if ($appOnBefore) {
         $appOnBefore($this);
      }

      // onbefore method
      if (method_exists($this, ServiceInterface::METHOD_ONBEFORE)) {
         $this->{ServiceInterface::METHOD_ONBEFORE}();
      }

      $output = null;
      // site interface
      if ($this->protocol == ServiceInterface::PROTOCOL_SITE) {
         // always uses main method?
         if ($this->useMainOnly || $this->isMain()) {
            $output = $this->{ServiceInterface::METHOD_MAIN}();
         } elseif (method_exists($this, $this->method)) {
            $output = call_user_func_array([$this, $this->method], $this->methodArgs);
         } else {
            // call fail::main
            $output = $this->{ServiceInterface::METHOD_MAIN}();
         }
      }
      // rest interface
      elseif ($this->protocol == ServiceInterface::PROTOCOL_REST) {
         // always uses main method?
         if ($this->useMainOnly) {
            $output = $this->{ServiceInterface::METHOD_MAIN}();
         } elseif (method_exists($this, $this->method)) {
            $output = call_user_func_array([$this, $this->method], $this->methodArgs);
         } else {
            // call fail::main
            $output = $this->{ServiceInterface::METHOD_MAIN}();
         }
      }

      // onafter method
      if (method_exists($this, ServiceInterface::METHOD_ONAFTER)) {
         $this->{ServiceInterface::METHOD_ONAFTER}();
      }

      // call app's onafter
      if ($appOnAfter) {
         $appOnAfter($this);
      }

      return $output;
   }

   /**
    * Set allowed request methods.
    *
    * @param  string ...$allowedRequestMethods
    * @return self
    */
   final public function setAllowedRequestMethods(array ...$allowedRequestMethods): self
   {
      $this->allowedRequestMethods = array_map('strtoupper', $allowedRequestMethods);

      return $this;
   }

   /**
    * Get allowed request methods.
    *
    * @return array
    */
   final public function getAllowedRequestMethods(): array
   {
      return $this->allowedRequestMethods;
   }

   /**
    * Check request method is allowed.
    *
    * @return bool
    */
   final public function isAllowedRequestMethod(string $requestMethod): bool
   {
      if (empty($this->allowedRequestMethods)) {
         return true;
      }

      return in_array($requestMethod, $this->allowedRequestMethods);
   }

   /**
    * Load service sprecific configs.
    *
    * @return self
    */
   final private function loadConfig(): self
   {
      $file = sprintf('./app/service/%s/config/config.php', $this->name);
      if (is_file($file)) {
         $this->config = new Config($file);
      }

      return $this;
   }

   /**
    * Load model.
    *
    * @return self
    */
   final private function loadModel(): self
   {
      $file = sprintf('./app/service/%s/model/model.php', $this->name);
      if (is_file($file)) {
         require($file);
      }

      return $this;
   }

   /**
    * View support.
    *
    * @param  string $file
    * @param  array  $data
    * @return void
    */
   final public function view(string $file, array $data = null)
   {
      // use both header/footer
      if ($this->useViewPartialAll || ($this->useViewPartialHead && $this->useViewPartialFoot)) {
         $this->view->displayHead($data);
         $this->view->display($file, $data);
         $this->view->displayFoot($data);
      }
      // use only header
      elseif ($this->useViewPartialHead && !$this->useViewPartialFoot) {
         $this->view->displayHead($data);
         $this->view->display($file, $data);
      }
      // use only footer
      elseif (!$this->useViewPartialHead && $this->useViewPartialFoot) {
         $this->view->display($file, $data);
         $this->view->displayFoot($data);
      }
      // use only view file
      else {
         $this->view->display($file, $data);
      }
   }

   /**
    * Check is default service (fail, main).
    *
    * @return bool
    */
   final public function isDefault(): bool
   {
      return (
         $this->name == ServiceInterface::SERVICE_MAIN ||
         $this->name == ServiceInterface::SERVICE_FAIL
      );
   }
}
