<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use froq\app\{Controller, Repository};

/**
 * A route stack class, able to such ops add, pack and resolve using RegExp utilities.
 *
 * @package froq
 * @object  froq\Router
 * @author  Kerem Güneş
 * @since   4.0
 */
final class Router
{
    /** @var array */
    private array $routes = [];

    /** @var array */
    private array $debug = [];

    /** @var array */
    private static array $options = [];

    /** @var array */
    private static array $optionsDefault = [
        'defaultController' => Controller::DEFAULT,
        'defaultAction'     => Controller::ACTION_DEFAULT,
        'endingSlashes'     => true,  'throwErrors' => true,
        'decodeUri'         => false, 'unicode'     => false,
    ];

    /**
     * Constructor.
     *
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $this->setOptions((array) $options);
    }

    /**
     * Get routes.
     *
     * @return array
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Get debug.
     *
     * @return array
     */
    public function debug(): array
    {
        return $this->debug;
    }

    /**
     * Set options.
     *
     * @param  array $options
     * @return void
     * @since  4.14
     */
    public function setOptions(array $options): void
    {
        self::$options = array_options($options, self::$optionsDefault);
    }

    /**
     * Get options.
     *
     * @return array
     * @since  4.14
     */
    public function getOptions(): array
    {
        return self::$options;
    }

    /**
     * Get option.
     *
     * @param  string     $key
     * @param  mixed|null $default
     * @return mixed|null
     * @since  5.3
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return self::$options[$key] ?? $default;
    }

    /**
     * Add a route to stack preparing its methods & calls with call arguments.
     *
     * @param  string          $route
     * @param  string          $methods
     * @param  string|callable $call
     * @param  array|null      $callArgs
     * @return void
     */
    public function addRoute(string $route, string $methods, string|callable $call, array $callArgs = null): void
    {
        $route  = trim($route);
        $routes = $this->routes();

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
     * Add multiple routes to stack.
     *
     * @param  array $routes
     * @return void
     */
    public function addRoutes(array $routes): void
    {
        // These generally comes from configuration.
        foreach ($routes as $i => $route) {
            // When route not wrapped in an array.
            is_int($i) || $route = [$i, $route];

            [$route, $call, $callArgs] = array_pad((array) $route, 3, null);

            if (is_array($call)) {
                // Multiple directives (eg: ["/book/:id", ["GET" => "Book.show", "PUT" => "Book.edit", ..]]).
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
     * Resolve given URI onto a defined route if possible and return a packed action/controller pairs,
     * otherwise return null that indicates no route found. Throw a `RouterException` if no routes provided
     * yet or no pattern / no call directive provided for a route.
     *
     * @param  string      $uri
     * @param  string|null $method
     * @param  array|null  $options
     * @return array|null
     * @throws froq\RouterException
     */
    public function resolve(string $uri, string $method = null, array $options = null): array|null
    {
        $routes = $this->routes();
        $routes || throw new RouterException('No route directives to resolve');

        $options  = array_options($options, self::$options);
        $patterns = [];

        foreach ($routes as $i => [$pattern]) {
            $pattern || throw new RouterException('No pattern given for route `%s`', $i);

            // Format named parameters if given.
            if (str_contains($pattern, ':')) {
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

            // Curlies are normal parens.
            $pattern = strtr($pattern, '{}', '()');

            // Escape delimiter.
            $pattern = addcslashes($pattern, '~');

            // Add optional slash to end.
            $options['endingSlashes'] && $pattern .= '/?';

            // See http://www.pcre.org/pcre.txt for verbs.
            $patterns[] = ' (*MARK:'. $i .') '. $pattern;
        }

        // Operator "x" is just for readability at debugging times.
        $pattern = "~^(?:\n" . join(" |\n", $patterns) . "\n)$~xAJ";

        // Apply options.
        $options['unicode'] && $pattern .= 'u';
        $options['decodeUri'] && $uri = rawurldecode($uri);

        // Normalize URI (removes repeating & ending slashes).
        $uri = '/' . preg_replace('~/+~', '/', trim($uri, '/'));

        $this->debug = ['uri' => $uri, 'pattern' => $pattern, 'mark' => null];

        $res = preg_match($pattern, $uri, $match, PREG_UNMATCHED_AS_NULL);

        // Check invalid-pattern causing errors.
        if (!$res && $options['throwErrors']) {
            $message = preg_error_message($code, 'preg_match');
            $message && throw new RouterException($message, code: $code);
        }

        if ($res) {
            $this->debug['match'] = $match;

            $mark = (int) $match['MARK'];
            if (empty($routes[$mark][1])) {
                throw new RouterException('No call directives found for route `%s`', $mark);
            }

            $this->debug['mark'] = $mark;

            $calls    = (array) $routes[$mark][1];
            $callArgs = $usedArgs = [];

            // Drop input & mark fields.
            $match = array_slice($match, 1, -1);

            // Handle conditional replacements, eg: ["/user/:x{login|logut}", "User.{x}"].
            foreach ($calls as $i => $call) {
                $rep = grep($call, '~{(\w+)}~');
                if ($rep) {
                    if (isset($match[$rep])) {
                        // Replace & tick ("-" for camel-case).
                        $calls[$i] = str_replace('{' . $rep . '}', '-' . $match[$rep] . '-', $call);
                        $usedArgs[$match[$rep]] = 1;
                    } else {
                        // Remove non-found replacements from call.
                        $calls[$i] = str_replace('{' . $rep . '}', '', $call);
                    }
                }
            }

            // Fill call arguments.
            $i = 0;
            foreach ($match as $key => $value) {
                if (is_string($key)) {
                    $value = $match[$i] ?? $value;
                    // Skip NULLs that set via PREG_UNMATCHED_AS_NULL.
                    if (isset($value) && !isset($usedArgs[$value])) {
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
     * Pack given calls with call arguments into an array structure by HTTP method or * that means
     * accapting all HTTP methods. Throw a `RouterException` if no valid action given or no controller
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
            // eg: ["/book/:id", ["*" => "Book.show", "PUT" => "Book.edit", ..]].
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
                throw new RouterException(
                    'Only string and callable actions are allowed, %t given',
                    $call
                );
            }
        }

        return $actions;
    }

    /**
     * Prepare a short call directive converting the call to a fully qualified controller/action
     * pairs and appending action parameters to the returning array. Return an empty array if an
     * invalid call directive given.
     *
     * @param  string $call
     * @param  array  $callArgs
     * @return array<string, string, array<string>>|array<null, null, null>
     */
    public static function prepare(string $call, array $callArgs = []): array
    {
        // Note: suffixes ("Controller" and "Action") must not be used in call directives,
        // eg: Index for IndexController, Index.foo for IndexController.fooAction.
        [$controller, $action] = split('.', $call, 2);

        // Return controller, action, actionParams.
        return $controller ? [
            self::prepareControllerName($controller),
            self::prepareActionName($action ?? self::$options['defaultAction']),
            $callArgs
        ] : [null, null, null];
    }

    /**
     * Prepare a controller & controller action name.
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
        if (str_contains($name, '-')) {
            $name = implode('', array_map('ucfirst', explode('-', $name)));
        }

        if ($suffix) {
            $name = ($suffix == Controller::SUFFIX || $suffix == Repository::SUFFIX)
                  ? ucfirst($name) : lcfirst($name);

            // Add/drop suffix.
            $name = !$suffixed ? (
                str_ends_with($name, $suffix)
                    ? substr($name, 0, -strlen($suffix))
                    : $name
            ) : ($name . $suffix);
        }

        return $name;
    }

    /**
     * Prepare a controller name.
     *
     * @param  string $controller
     * @param  bool   $full
     * @return string
     */
    public static function prepareControllerName(string $name, bool $full = true): string
    {
        $name = trim($name, '/\\');
        $base = null;

        if ($name == Controller::NAME_DEFAULT) {
            $name = self::$options['defaultController'];
        }

        // Check whether controller is a sub-controller.
        if (($pos = strrpos($name, '/')) || ($pos = strrpos($name, '\\'))) {
            $base = substr($name, 0, $pos);
            $name = substr($name, $pos + 1);

            // Convert namespace separators.
            $base = str_replace('/', '\\', $base);

            // Drop default namespace prefix.
            if (str_starts_with($base, Controller::NAMESPACE)) {
                $base = substr($base, strlen(Controller::NAMESPACE) + 1);
            }
        }

        $name = self::prepareName($name, Controller::SUFFIX);

        // Make controller fully named & namespaced.
        if ($full) {
            $name = !$base ? Controller::NAMESPACE . '\\' . $name . Controller::SUFFIX
                           : Controller::NAMESPACE . '\\' . $base . '\\' . $name . Controller::SUFFIX;
        }

        return $name;
    }

    /**
     * Prepare a (controller) action name.
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
     * Prepare calls for a route.
     */
    private static function prepareCalls(array $routes, int $i): array
    {
        return (array) ($routes[$i][1] ?? []);
    }

    /**
     * Prepare methods for a route.
     */
    private static function prepareMethods(string $methods): array
    {
        // Non-array calls without method that accept all (eg: ["/book/:id", "Book.show"]).
        $methods = (string) ($methods ?: '*');

        // Multiple methods can be given (eg: ["/book/:id", ["GET,POST" => "Book.index"]]).
        return array_map('strtoupper', split(',', $methods));
    }
}
