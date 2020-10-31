<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq;

use froq\mvc\{Controller, Model};
use froq\RouterException;

/**
 * Router.
 * @package froq
 * @object  froq\Router
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Router
{
    /**
     * Routes.
     * @var array
     */
    private array $routes = [];

    /**
     * Debug.
     * @var array
     */
    private array $debug = [];

    /**
     * Options.
     * @var array
     * @since 4.12
     */
    private static array $options = [];

    /**
     * Options default.
     * @var array
     * @since 4.12
     */
    private static array $optionsDefault = [
        'defaultController' => Controller::DEFAULT,
        'defaultAction'     => Controller::ACTION_DEFAULT,
        'unicode'           => false,
        'decodeUri'         => false,
        'endingSlashes'     => true,
    ];

    /**
     * Constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        self::setOptions($options ?? []);
    }

    /**
     * Gets the routes property.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Gets the debug property.
     *
     * @return array
     */
    public function getDebug(): array
    {
        return $this->debug;
    }

    /**
     * Set options.
     * @param  array $options
     * @return self
     * @since  4.14
     */
    public final function setOptions(array $options): self
    {
        self::$options = array_replace(self::$optionsDefault, $options);

        return $this;
    }

    /**
     * Get options.
     * @return array
     * @since  4.14
     */
    public final function getOptions(): array
    {
        return self::$options;
    }

    /**
     * Adds a route to routes stack.
     *
     * @param  string          $route
     * @param  string          $methods
     * @param  string|callable $call
     * @param  array|null      $callArgs
     * @return void
     */
    public function addRoute(string $route, string $methods, $call, array $callArgs = null): void
    {
        $route  = trim($route);
        $routes = $this->getRoutes();

        // Chop "/" from end.
        if ($route != '/') {
            $route = rtrim($route, '/');
        }

        $i = count($routes);

        // Find current route index if exists.
        foreach ($routes as $_i => [$_route]) {
            if ($route === $_route) {
                $i = $_i;
                break;
            }
        }

        $calls   = self::prepareCalls($routes, $i);
        $methods = self::prepareMethods($methods);

        foreach ($methods as $method) {
            $calls[$method] = $call;
        }

        $this->routes[$i] = [$route, $calls, $callArgs];
    }

    /**
     * Adds multiple routes to routes stack.
     *
     * @param array $routes
     */
    public function addRoutes(array $routes): void
    {
        // These generally comes from configuration.
        foreach ($routes as $route) {
            @ [$route, $call, $callArgs] = (array) $route;

            if (is_array($call)) {
                // Multiple directives (eg: ["/book/:id", ["GET" => "Book.show", "POST" => "Book.edit", ..]]).
                foreach ($call as $method => $call) {
                    $this->addRoute($route, $method, $call, (array) $callArgs);
                }
            } else {
                // Single directive (eg: ["/book/:id", "Book.show"]).
                $this->addRoute($route, '*', $call, (array) $callArgs);
            }
        }
    }

    /**
     * Resolves the given URI onto a defined route if possible and returns a packed action/controller
     * pairs, otherwise returns null that indicates no route found. Throws `RouterException` if no
     * routes provided yet or no pattern / no call directive provided for a route.
     *
     * @param  string      $uri
     * @param  string|null $method
     * @param  array|null  $options
     * @return ?array
     * @throws froq\RouterException
     */
    public function resolve(string $uri, string $method = null, array $options = null): ?array
    {
        $routes = $this->getRoutes();
        if ($routes == null) {
            throw new RouterException('No route directives exists yet to resolve');
        }

        // Update options.
        if ($options != null) {
            self::$options = array_replace(self::$optionsDefault, $options);
        }

        $patterns = [];
        foreach ($routes as $i => [$pattern]) {
            if (empty($pattern)) {
                throw new RouterException('No pattern given for route "%s"', [$i]);
            }

            // Format named parameters if given.
            if (strpos($pattern, ':')) {
                $pattern = preg_replace_callback(
                    // Eg: /:id[\d] or /:tab{show|hide}
                    '~(?<!\?):(\w+)(?:(?<c>[\[\{])(.+?)[\]\}])?~',
                    function ($match) {
                        $name = $match[1];

                        // Simple (eg: /book/:id => /book/(?<id>[^/]+)).
                        if (empty($match['c'])) {
                            return '(?<'. $name .'>[^/]+)';
                        }

                        switch ($match['c']) {
                            // Sets (eg: /book/:id[\d] => /book/(?<id>[\d]+)).
                            case '[': return '(?<'. $name .'>['. $match[3] .']+)';
                            // Optionals (eg: /book/:tab{show|hide} => /book/(?<tab>show|hide)).
                            case '{': return '(?<'. $name .'>'. $match[3] .')';
                        }
                    },
                    $pattern
                );
            }

            // Escape delimiter.
            $pattern = addcslashes($pattern, '~');

            // Add optional slash to end.
            self::$options['endingSlashes'] && $pattern .= '/?';

            // See http://www.pcre.org/pcre.txt for verbs.
            $patterns[] = ' (*MARK:'. $i .') '. $pattern;
        }

        // Operator "x" is just for readability at debugging times.
        $pattern = "~^(?:\n". join(" |\n", $patterns) ."\n)$~xAJ";

        // Apply options.
        self::$options['unicode'] && $pattern .= 'u';
        self::$options['decodeUri'] && $uri = rawurldecode($uri);

        // Normalize URI (removes repeating & ending slashes).
        $uri = '/'. preg_replace('~/+~', '/', trim($uri, '/'));

        $this->debug = ['uri' => $uri, 'pattern' => $pattern, 'mark' => null];

        if (preg_match($pattern, $uri, $match, PREG_UNMATCHED_AS_NULL)) {
            $this->debug['match'] = $match;

            $mark = (int) $match['MARK'];
            if (empty($routes[$mark][1])) {
                throw new RouterException('No call directives found for route "%s"', [$mark]);
            }

            $this->debug['mark'] = $mark;

            $calls    = (array) $routes[$mark][1];
            $callArgs = [];

            // Drop input & mark fields.
            $match = array_slice($match, 1, -1);

            // Fill call arguments.
            $i = 0;
            foreach ($match as $key => $value) {
                if (is_string($key)) {
                    $value = $match[$i] ?? $value;
                    // Skip NULLs that set via PREG_UNMATCHED_AS_NULL.
                    if (isset($value)) {
                        $callArgs[$key] = $value;
                    }
                    // Step back, so we need after-string indexes only.
                    $i--;
                }
                $i++;
            }

            // Add extra call arguments if provided (in config as third field).
            if (isset($routes[$mark][2])) {
                $callArgs = array_merge($callArgs, (array) $routes[$mark][2]);
            }

            $pack = self::pack($calls, $callArgs);

            // Try to return defined method or all (*) methods.
            return $method ? $pack[$method] ?? $pack['*'] ?? null
                           : $pack;
        }

        $this->debug['match'] = $match;

        // Not found.
        return null;
    }

    /**
     * Packs given calls with call arguments into an array structure by HTTP method or * that means
     * accapting all HTTP methods. Throws a `RouterException` if no valid action given or no controller
     * given for controller based routes.
     *
     * @param  array $calls
     * @param  array $callArgs
     * @return array
     * @throws froq\RouterException
     */
    public static function pack(array $calls, array $callArgs): array
    {
        $actions      = [];
        $actionParams = $callArgs;

        foreach ($calls as $methods => $call) {
            $methods = self::prepareMethods($methods);

            // Controller actions.
            // eg: ["/book/:id", "Book.show", ..].
            // eg: ["/book/:id", ["*" => "Book.show", "POST" => "Book.edit", ..]].
            if (is_string($call)) {
                [$controller, $action] = self::prepare($call);

                if (!$controller) {
                    throw new RouterException('No controller given in route');
                }

                foreach ($methods as $method) {
                    $actions[$method] = [$controller, $action, $actionParams];
                }
            }
            // Callable actions.
            // eg: $app->get("/book/:id", function ($id) { .. }).
            // eg: $app->route("/book/:id", "GET", function ($id) { .. }).
            elseif (is_callable($call)) {
                [$controller, $action] = [self::$options['defaultController'], $call];

                foreach ($methods as $method) {
                    $actions[$method] = [$controller, $action, $actionParams];
                }
            }
            // No valid route options.
            else {
                throw new RouterException('Only string and callable actions are allowed');
            }
        }

        return $actions;
    }

    /**
     * Prepares a short call directive converting the call to a fully qualified controller/action
     * pairs and appending action parameters to the returning array. Returns an empty array if an
     * invalid call directive given.
     *
     * @param  string $call
     * @param  array  $callArgs
     * @return array<string, string, array<string>>|array<null, null, null>
     */
    public static function prepare(string $call, array $callArgs = []): array
    {
        // Note: suffixes ("Controller" and "Action") must not be used in call directives (
        // eg: Index for IndexController, Index.foo for IndexController.fooAction).
        @ [$controller, $action] = explode('.', $call);
        if (!$controller) {
            return [null, null, null];
        }

        // Return controller, action, actionParams.
        return [
            self::prepareControllerName($controller),
            self::prepareActionName($action ?: self::$options['defaultAction']),
            $callArgs
        ];
    }

    /**
     * Prepares a controller & controller action name.
     *
     * @param  string      $name
     * @param  string|null $suffix
     * @param  bool        $suffixed
     * @return string
     * @since  4.2
     */
    public static function prepareName(string $name, string $suffix = null, bool $suffixed = false): string
    {
        // Titleize.
        if (strpos($name, '-')) {
            $name = implode('', array_map('ucfirst', explode('-', $name)));
        }

        if ($suffix != null) {
            $name = ($suffix == Controller::SUFFIX || $suffix == Model::SUFFIX)
                  ? ucfirst($name) : lcfirst($name);

            // Add/drop suffix.
            $name = !$suffixed
                  ? (strsfx($name, $suffix) ? strsub($name, 0, -strlen($suffix)) : $name)
                  : ($name . $suffix);
        }

        return $name;
    }

    /**
     * Prepares a controller name.
     *
     * @param  string $controller
     * @param  bool   $full
     * @return string
     */
    public static function prepareControllerName(string $name, bool $full = true): string
    {
        if ($name == Controller::NAME_DEFAULT) {
            $name = self::$options['defaultController'];
        }

        $name = self::prepareName($name, Controller::SUFFIX);

        // Fix namespaced name.
        if ($pos = strrpos($name, '\\')) {
            $name = strsub($name, $pos + 1);
        }

        // Make controller fully named & namespaced.
        if ($full) {
            $name = Controller::NAMESPACE .'\\'. $name . Controller::SUFFIX;
        }

        return $name;
    }

    /**
     * Prepares a (controller) action name.
     *
     * @param  string $name
     * @param  bool   $full
     * @return string
     */
    public static function prepareActionName(string $name, bool $full = true): string
    {
        if ($name == Controller::NAME_DEFAULT) {
            $name = self::$options['defaultAction'];
        }

        $name = self::prepareName($name, Controller::ACTION_SUFFIX);

        // Make action suffixed, skipping special actions (index & error).
        if ($full && ($name != Controller::INDEX_ACTION && $name != Controller::ERROR_ACTION)) {
            $name .= Controller::ACTION_SUFFIX;
        }

        return $name;
    }

    /**
     * Prepares calls for a route.
     *
     * @param  array $routes
     * @param  int   $i
     * @return array
     */
    private static function prepareCalls(array $routes, int $i): array
    {
        return (array) ($routes[$i][1] ?? []);
    }

    /**
     * Prepares methods for a route.
     *
     * @param  string $methods
     * @return array<string>
     */
    private static function prepareMethods(string $methods): array
    {
        // Non-array calls without a method that accepts all (eg: ["/book/:id", "Book.show"]).
        $methods = (string) ($methods ?: '*');

        // Multiple methods can be given (eg: ["/book/:id", ["GET,POST" => "Book.show"]]).
        return array_map('strtoupper', explode(',', $methods));
    }
}
