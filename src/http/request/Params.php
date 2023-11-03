<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

use froq\util\Arrays;

/**
 * Params, for getting get/post/cookie params.
 *
 * @package froq\http\request
 * @class   froq\http\request\Params
 * @author  Kerem Güneş
 * @since   1.0
 * @static
 */
class Params extends \StaticClass
{
    /**
     * Get all params by GPC sort.
     *
     * @return array
     * @since  4.0
     */
    public static function all(): array
    {
        return [$_GET, $_POST, $_COOKIE];
    }

    /**
     * Get one/many/all $_GET params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options @see fetch()
     * @return mixed
     */
    public static function get(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return self::fetch($_GET, $name, $default, ...$options);
    }

    /**
     * Check one/many/all $_GET params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public static function hasGet(string|array $name = null): bool
    {
        return self::check($_GET, $name);
    }

    /**
     * Get one/many/all $_POST params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options @see fetch()
     * @return mixed
     */
    public static function post(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return self::fetch($_POST, $name, $default, ...$options);
    }

    /**
     * Check one/many/all $_POST params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public static function hasPost(string|array $name = null): bool
    {
        return self::check($_POST, $name);
    }

    /**
     * Get one/many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $name
     * @param  mixed|null                $default
     * @param  mixed                  ...$options @see fetch()
     * @return mixed
     */
    public static function cookie(string|array $name = null, mixed $default = null, mixed ...$options): mixed
    {
        return self::fetch($_COOKIE, $name, $default, ...$options);
    }

    /**
     * Check one/many/all $_COOKIE params.
     *
     * @param  string|array<string>|null $name
     * @return bool
     */
    public static function hasCookie(string|array $name = null): bool
    {
        return self::check($_COOKIE, $name);
    }

    /**
     * Check params for given source by name(s).
     */
    private static function check(array|null $source, string|array|null $name): bool
    {
        if (empty($source)) {
            return false;
        }

        if ($name === null || $name === '*') {
            return !empty($source);
        }

        foreach ((array) $name as $name) {
            if (self::fetch($source, $name) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fetch params from given source by name(s).
     */
    private static function fetch(
        array|null        $source,
        string|array      $name      = null,  // Eg: "id", ["id", "name"] or "*" for all.
        mixed             $default   = null,  // Eg: "ok", false or ["ok"], [false] for multi-names.
        callable|string   $map       = null,  // Eg: "int", "intval" or "trim|upper".
        callable          $filter    = null,  // Eg: "is_numeric" or any filter function.
        bool              $trim      = false, // Set trim as map function, but can be disable for JSON inputs etc.
        bool              $combine   = false  // Combine names/values, not for dotted name notations (eg: "foo.bar").
    ): mixed
    {
        $all = ($name === null || $name === '*');

        // Some speed..
        if (empty($source)) {
            if ($all) return [];

            if (is_array($name)) {
                $default = array_pad((array) $default, $count = count($name), null);
                $combine && $default = array_combine($name, array_slice($default, 0, $count));
            }

            return $default;
        }

        $names  = (array) $name;
        $values = [];

        if (!$all && is_string($name)) {
            // For #1 & #2 below.
            $values[0] = Arrays::get($source, $name, $default);
            if ($values[0] === null) {
                $values = [];
            }
        } elseif (!$all) {
            $values = Arrays::getAll($source, $names, (array) $default);
        } else {
            $values = $source;
        }

        // #1
        if ($values) {
            // Set trim as default mapper, if true & no map given.
            if ($trim && !$map) $map = 'trim';

            // Apply map & filter, if map or filter given.
            if ($map || $filter) {
                $values = self::applyMapFilter($values, $map, $filter);
            }
        }

        // Won't work dotted names (eg: "foo.bar").
        if (!$all && $combine) {
            return Arrays::compose($names, $values);
        }

        // #2
        if (!$all && is_string($name)) {
            return Arrays::first($values);
        }

        return $values;
    }

    /**
     * Apply map/filter.
     */
    private static function applyMapFilter(array $values, callable|string|null $map, callable|null $filter): array
    {
        // For safely mapping arrays/nulls.
        if ($map) $map = fn($v) => self::wrapMap($map, $v);

        $map    && $values = Arrays::map($values, $map, true);
        $filter && $values = Arrays::filter($values, $filter);

        return $values;
    }

    /**
     * Array/null safe map wrap, also multi-map aware.
     */
    private static function wrapMap(callable|string $map, mixed $input): mixed
    {
        // Nulls stay nulls.
        if (is_null($input)) {
            return null;
        }

        // Regular map.
        if (is_callable($map)) {
            // @cancel: Try/catch is more fast.
            // $type = (new \ReflectionCallable($map))->getParameter(0)?->getType();
            // if ($type?->isBuiltin()) {
            //     settype($input, $type->getName());
            // }
            // return $map($input);

            try {
                return $map($input);
            } catch (\TypeError) {
                return $map((string) $input);
            }
        }

        // Multi-map (eg: "trim|upper").
        foreach (explode('|', $map) as $map) {
            if (!$map) continue;

            // Wraps "[]" for safe map calls.
            try {
                $input = Arrays::map([$input], $map, true)[0];
            } catch (\TypeError) {
                $input = Arrays::map([(string) $input], $map, true)[0];
            }
        }

        return $input;
    }
}
