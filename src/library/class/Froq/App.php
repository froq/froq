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

namespace Froq;

use Froq\Logger\Logger;
use Froq\Database\Database;
use Froq\Util\{Config, Session};
use Froq\Http\{Request, Response};
use Froq\Service\{ServiceAdapter, ServiceInterface};
use Froq\Util\Traits\{SingleTrait as Single, GetterTrait as Getter};
use Froq\Handler\{Error as ErrorHandler, Exception as ExceptionHandler, Shutdown as ShutdownHandler};

/**
 * @package Froq
 * @object  Froq\App
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class App
{
   /**
    * Single.
    * @object Froq\Util\Traits\SingleTrait
    */
   use Single;

   /**
    * Getter.
    * @object Froq\Util\Traits\GetterTrait
    */
   use Getter;

   /**
    * App environments.
    * @const string
    */
   const ENV_DEVELOPMENT = 'development',
         ENV_STAGE       = 'stage',
         ENV_PRODUCTION  = 'production';

   /**
    * App default root.
    * @const string
    */
   const DEFAULT_ROOT = '/';

   /**
    * App environment.
    * @var string
    */
   private $env;

   /**
    * App default root.
    * @var string
    */
   private $root = self::DEFAULT_ROOT;

   /**
    * Service object.
    * @var Froq\Service\Service
    */
   private $service;

   /**
    * Session object.
    * @var Froq\Util\Session
    */
   private $session;

   /**
    * Request objects.
    * @var Froq\Http\Request
    */
   private $request, $response;

   /**
    * Response objects.
    * @var Froq\Http\Response
    */

   /**
    * Configuration object.
    * @var Froq\Util\Config
    */
   private $config;

   /**
    * Database object.
    * @var Froq\Database\Database
    */
   private $db;

   /**
    * Logger object.
    * @var Froq\Logger\Logger
    */
   private $logger;

   /**
    * Handlers (output etc.).
    * @var array
    */
   private $handlers = [];

   /**
    * Constructor.
    *
    * @param array $cfg
    */
   final private function __construct(array $cfg = [])
   {
      // logger
      $this->logger = new Logger();

      // set default config first
      $this->setConfig($cfg);

      // set app as global
      set_global('app', $this);

      // load core helpers
      $files = glob($cfg['app']['dir']['function'] .'/*.php');
      foreach ($files as $file) {
         require_once($file);
      }

      // load core globals
      if (is_file($file = './app/global/def.php')) {
         require_once($file);
      }
      if (is_file($file = './app/global/fun.php')) {
         require_once($file);
      }

      // composer
      $autoload = './vendor/autoload.php';
      if (is_file($autoload)) {
         $autoload = require($autoload);
         $autoload->register();
      }

      // set handlers
      $this->setErrorHandler();
      $this->setExceptionHandler();
      $this->setShutdownHandler();

      $this->db = new Database();
   }

   /**
    * Restore defaults.
    *
    * @return void
    */
   final public function __destruct()
   {
      restore_include_path();
      restore_error_handler();
      restore_exception_handler();
   }

   /**
    * Run!
    *
    * @return void
    * @throws \RuntimeException
    */
   final public function run()
   {
      if (!$this->config) {
         throw new \RuntimeException('Call setConfig() first to get run app!');
      }

      // re-set app as global
      set_global('app', $this);

      $this->request = new Request();
      $this->response = new Response();

      // set app defaults
      $this->setDefaults();

      // security & performans checks
      if ($halt = $this->haltCheck()) {
         $this->halt($halt);
      }

      // hold output buffer
      $this->startOutputBuffer();

      $this->service = (new ServiceAdapter($this))
         ->getService();

      // sessions used for only "site" services
      if ($this->service->useSession
         && $this->service->protocol == ServiceInterface::PROTOCOL_SITE
      ) {
         $this->session = Session::init($this->config['app.session.cookie']);
      }

      $output = '';
      if (!$this->service->isAllowedRequestMethod($this->request->method)) {
         // set fail stuff (bad request)
         $this->response->setStatus(405);
         $this->response->setContentType('none');
      } else {
         $output = $this->service->run();
      }

      // end output buffer
      $this->endOutputBuffer($output);
   }

   /**
    * Set app environment.
    *
    * @param  string $env
    * @return self
    */
   final public function setEnv(string $env): self
   {
      $this->env = $env;

      return $this;
   }

   /**
    * Set app root.
    *
    * @param  string $root
    * @return self
    */
   final public function setRoot(string $root): self
   {
      $this->root = $root;

      return $this;
   }

   /**
    * Set app config.
    *
    * @param  string $config
    * @return self
    */
   final public function setConfig(array $config): self
   {
      if ($this->config) {
         $config = Config::merge($this->config->getData(), $config);
      }
      $this->config = new Config($config);

      // set log options
      $this->logger
         ->setLevel($this->config['app.logger.level'])
         ->setDirectory($this->config['app.logger.directory'])
         ->setFilenameFormat($this->config['app.logger.filenameFormat']);

      return $this;
   }

   /**
    * Set a handler.
    *
    * @param  string   $name
    * @param  callable $handler
    * @return self
    */
   final public function setHandler(string $name, callable $handler): self
   {
      $this->handlers[strtolower($name)] = $handler;

      return $this;
   }

   /**
    * Get a handler.
    *
    * @param  string $name
    * @return callable|null
    */
   public function getHandler(string $name)
   {
      return $this->handlers[strtolower($name)] ?? null;
   }

   /**
    * Set app defaults.
    *
    * @return self
    */
   final public function setDefaults(): self
   {
      $cfg = ['locale'   => $this->config['app.locale'],
              'encoding' => $this->config['app.encoding'],
              'timezone' => $this->config['app.timezone'],
      ];

      // multibyte
      mb_internal_encoding($cfg['encoding']);

      // timezone
      date_default_timezone_set($cfg['timezone']);

      // default charset
      ini_set('default_charset', $cfg['encoding']);

      // locale stuff
      $locale = sprintf('%s.%s', $cfg['locale'], $cfg['encoding']);
      setlocale(LC_TIME, $locale);
      setlocale(LC_NUMERIC, $locale);
      setlocale(LC_MONETARY, $locale);

      return $this;
   }

   /**
    * Start output buffer.
    *
    * @return void
    */
   final public function startOutputBuffer()
   {
      ini_set('implicit_flush', '1');

      $gzipOptions = $this->config->get('app.gzip', []);
      if (!empty($gzipOptions)) {
         if (!headers_sent()) {
            ini_set('zlib.output_compression', '0');
         }

         // detect client gzip status
         if (isset($this->request->headers['accept_encoding'])
            && (false !== strpos($this->request->headers['accept_encoding'], 'gzip'))) {
            $this->response->setGzipOptions($gzipOptions);
         }
      }

      // start!
      ob_start();
   }

   /**
    * End output buffer.
    *
    * @param  mixed $output
    * @return void
    */
   final public function endOutputBuffer($output = null)
   {
      // handle redirections
      if ($this->response->status->code >= 300 &&
          $this->response->status->code <= 399
       ) {
         // clean & turn off output buffering
         while (ob_get_level()) {
            ob_end_clean();
         }
         // no content!
         $this->response->setContentType('none');
      }
      // handle outputs
      else {
         // print'ed service methods return "null"
         if ($output === null) {
            $output = '';
            while (ob_get_level()) {
               $output .= ob_get_clean();
            }
         }

         // use user output handler if provided
         $outputHandler = $this->getHandler('output');
         if ($outputHandler) {
            $output = $outputHandler($output);
         }

         // set response body
         $this->response->setBody($output);
      }

      // send response cookies, headers and body
      $this->response->sendCookieAll();
      $this->response->sendHeaderAll();
      $this->response->send();
   }

   /**
    * Check app environment is development.
    *
    * @return bool
    */
   final public function isDev(): bool
   {
      return ($this->env == self::ENV_DEVELOPMENT);
   }

   /**
    * Check app environment is stage.
    *
    * @return bool
    */
   final public function isStage(): bool
   {
      return ($this->env == self::ENV_STAGE);
   }

   /**
    * Check app environment is production.
    *
    * @return bool
    */
   final public function isProduction(): bool
   {
      return ($this->env == self::ENV_PRODUCTION);
   }

   /**
    * Set error handler.
    *
    * @return void
    */
   final public function setErrorHandler()
   {
      set_error_handler(ErrorHandler::handler());
   }

   /**
    * Set exception handler.
    *
    * @return void
    */
   final public function setExceptionHandler()
   {
      set_exception_handler(ExceptionHandler::handler());
   }

   /**
    * Set shutdown handler.
    *
    * @return void
    */
   final public function setShutdownHandler()
   {
      register_shutdown_function(ShutdownHandler::handler());
   }

   /**
    * Load time stats.
    *
    * @return string
    */
   final public function loadTime(): string
   {
      if (defined('APP_START_TIME')) {
         return sprintf('%.10f', (microtime(true) - APP_START_TIME));
      }

      return '';
   }

   /**
    * Halt an execution.
    *
    * @param  string $status
    * @return void
    */
   final private function halt(string $status)
   {
      header(sprintf('%s %s', $_SERVER['SERVER_PROTOCOL'], $status));
      header('Connection: close');
      header('Content-Type: none');
      header('Content-Length: 0');
      header_remove('X-Powered-By');
      exit(1);
   }

   /**
    * Halt check for security & safety.
    *
    * @return string
    */
   final private function haltCheck(): string
   {
      // check request count
      $maxRequest = $this->config->get('app.security.maxRequest');
      if ($maxRequest && count($_REQUEST) > $maxRequest) {
         return '429 Too Many Requests';
      }

      // check user agent
      $allowEmptyUserAgent = $this->config->get('app.security.allowEmptyUserAgent');
      if ($allowEmptyUserAgent === false
         && (!isset($_SERVER['HTTP_USER_AGENT']) || !trim($_SERVER['HTTP_USER_AGENT']))) {
         return '400 Bad Request';
      }

      // check client host
      $hosts = $this->config->get('app.hosts');
      if (!empty($hosts)
         && (!isset($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], $hosts))) {
         return '400 Bad Request';
      }

      // check file extension
      $allowFileExtensionSniff = $this->config->get('app.security.allowFileExtensionSniff');
      if ($allowFileExtensionSniff === false
         && preg_match('~\.(p[hyl]p?|rb|cgi|cf[mc]|p(pl|lx|erl)|aspx?)$~i',
               parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
         return '400 Bad Request';
      }

      // check service load
      if (sys_getloadavg()[0] > $this->config->get('app.loadAvg')) {
         return '503 Service Unavailable';
      }

      return '';
   }
}
