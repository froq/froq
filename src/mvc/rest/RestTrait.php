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

namespace froq\mvc\rest;

use froq\mvc\rest\RestException;

/**
 * Rest Trait.
 *
 * Represents a trait entity that can be used for RESTful controllers that responds only such
 * requests via GET, POST etc. methods and defines those named methods as controller methods.
 *
 * Note: User controller can/must implement `RestInterface` and call `rest()` method in `index()`
 * method.
 *
 * Example for a self index(): `return this.rest(...params)`
 * Example for a parent index(): `if this instanceof RestInterface: return this.rest(...params)`.
 *
 * @package froq\mvc\rest
 * @object  froq\mvc\rest\RestTrait
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.9
 */
trait RestTrait
{
    /**
     * Calls a rest methods like `get()`, `post()` etc. those defined in user controller.
     *
     * Note: this method must be called in `index()` method.
     *
     * @param  ... $params
     * @return any
     */
    protected final function rest(...$params)
    {
        $method = $this->request->method()->toString();

        if (method_exists($this, $method)) {
            return $this->call($method, $params, false);
        }

        throw new RestException('No "%s()" method defined on "%s" class for "%s" calls',
                [strtolower($method), get_class($this), strtoupper($method)]);
    }
}
