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

namespace froq\mvc;

use Reflector, ReflectionMethod, ReflectionFunction;

/**
 * Action.
 *
 * Represents an action entity which prepares action parameters and calls the target method or
 * callable (function) wraping its execution.
 *
 * @package froq\mvc
 * @object  froq\mvc\Action
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Action
{
    /**
     * Calls an object's method in a wrapped scope with given parameters. The target method is
     * generally comes from route configuration, App's `route()` method or other shortcut route
     * methods like `get()`, `post()`, eg: `$app->get("/book/:id", "Book.show")`.
     *
     * @param  object $object
     * @param  string $action
     * @param  array  $actionParams
     * @return any
     */
    public static function call(object $object, string $action, array $actionParams = [])
    {
        $actionParams = self::paramize(new ReflectionMethod($object, $action), $actionParams);

        return self::execute([$object, $action], $actionParams);
    }

    /**
     * Calls a callable (function) in a wrapped scope with given parameters. The target callable is
     * generally comes from App's `route()` method or other shortcut route methods like `get()`,
     * `post()`, eg: `$app->get("/book/:id", function ($id) { .. })`.
     *
     * @param  object $object
     * @param  string $action
     * @param  array  $actionParams
     * @return any
     */
    public static function callCallable(callable $action, array $actionParams = [])
    {
        $actionParams = self::paramize(new ReflectionFunction($action), $actionParams);

        return self::execute($action, $actionParams);
    }

    /**
     * Prepares an action's parameters.
     *
     * @param  Reflector $reflector
     * @param  array     $actionParams
     * @return array
     */
    private static function paramize(Reflector $reflector, array $actionParams): array
    {
        $params = [];

        foreach ($reflector->getParameters() as $i => $param) {
            // Action parameter can be named or indexed.
            $params[] = $actionParams[$param->name] ?? $actionParams[$i] ?? (
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }

        return $params;
    }

    /**
     * Executes an action (a class method or function). The execution is wrapped in an output
     * buffer stack and returned the called action's return or output buffer that captured from
     * an `echo`, `print` or `include` (view) command.
     *
     * @param  callable $action
     * @param  array    $actionParams
     * @return any
     */
    private static function execute(callable $action, array $actionParams)
    {
        ob_start();
        $return = call_user_func_array($action, $actionParams);
        $return = $return ?? ob_get_clean();

        if ($return === '') {
            $return = null;
        }

        return $return;
    }
}
