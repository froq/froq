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

use froq\RouterException;
use froq\mvc\Controller;

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
    private array $routes;

    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Gets the routes property.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes ?? [];
    }

    /**
     * Adds a route to routes stack.
     *
     * @param  string          $route
     * @param  string          $methods
     * @param  string|callable $call
     * @return void
     */
    public function addRoute(string $route, string $methods, $call): void
    {
        $route = trim($route);
        if ($route != '/') {
            $route = rtrim($route, '/');
        }

        $routes = $this->getRoutes();

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

        $this->routes[$i] = [$route, $calls];
    }

    /**
     * Adds multiple routes to routes stack.
     *
     * @param array<string|callable>|array<array<string|callable>> $routes
     */
    public function addRoutes(array $routes): void
    {
        // These generally comes from configuration.
        foreach ($routes as [$route, $call]) {
            if (is_array($call)) {
                // Multiple directives (eg: ["/book/:id", ["GET" => "Book.show", "POST" => "Book.edit", ..]]).
                foreach ($call as $method => $call) {
                    $this->addRoute($route, $method, $call);
                }
            } else {
                // Single directive (eg: ["/book/:id", "Book.show"]).
                $this->addRoute($route, '*', $call);
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
        if (empty($routes)) {
            throw new RouterException('No route directives found to resolve');
        }

        $patterns = [];
        foreach ($routes as $i => [$pattern]) {
            if (empty($pattern)) {
                throw new RouterException('No pattern given for route "%s"', [$i]);
            }

            // Format named parameters if given.
            if (strpos($pattern, ':')) {
                $pattern = preg_replace_callback(
                    '~(?<!\?):(\w+)(?:(?<c>[\[\{])(.+)[\]\}])?~',
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

            // See http://www.pcre.org/pcre.txt for verbs.
            $patterns[] = ' (*MARK:'. $i .') '. $pattern;
        }

        // Operator "x" is just for readability at debugging times.
        $pattern = "~^(?:\n". join(" |\n", $patterns) ."\n)$~xAJ";

        // Apply options.
        !empty($options['unicode']) && $pattern .= 'u';
        !empty($options['decodeUri']) && $uri = rawurldecode($uri);

        // Normalize URI (removes repeating & ending slashes).
        $uri = '/'. preg_replace('~/+~', '/', trim($uri, '/'));

        if (preg_match($pattern, $uri, $match, PREG_UNMATCHED_AS_NULL)) {
            $mark = (int) $match['MARK'];
            if (empty($routes[$mark][1])) {
                throw new RouterException('No call directives found for route "%s"', [$mark]);
            }

            $calls    = (array) $routes[$mark][1];
            $callArgs = [];

            // Drop input & mark fields.
            $match = array_slice($match, 1, -1);

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

            $pack = self::pack($calls, $callArgs);

            return ($method != null) ? $pack[$method] ?? $pack['*'] ?? null : $pack;
        }

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

        foreach ($calls as $methods => $action) {
            $methods = self::prepareMethods($methods);

            // Controller actions.
            // eg: ["/book/:id", "Book.show", ..].
            // eg: ["/book/:id", ["*" => "Book.show", "POST" => "Book.edit", ..]].
            if (is_string($action)) {
                @ [$controller, $action] = self::prepare($action);
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
            elseif (is_callable($action)) {
                foreach ($methods as $method) {
                    $actions[$method] = [Controller::DEFAULT, $action, $actionParams];
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
     * @return array<string, string, array<string>>|array<void>
     */
    public static function prepare(string $call, array $callArgs = []): array
    {
        // Suffixes ("Controller" and "Action") must not be used in call directives.
        @ [$controller, $action] = explode('.', $call, 2);
        if (!$controller) {
            return [];
        }

        // Make controller fully named & namespaced.
        if ($controller == Controller::NAME_DEFAULT) {
            $controller = Controller::DEFAULT; // For callables, @default directives and App.error().
        } else {
            $controller = sprintf('%s\%s%s', Controller::NAMESPACE, $controller, Controller::SUFFIX); // Methods.
        }

        // Make action default as index.
        $action = $action ?: Controller::INDEX_ACTION;

        // Add "Action" suffix if available.
        if ($action != Controller::INDEX_ACTION && $action != Controller::ERROR_ACTION) {
            $action .= Controller::ACTION_SUFFIX;
        }

        return [$controller, $action, ($actionParams = $callArgs)];
    }

    /**
     * Prepares calls for a routes.
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
