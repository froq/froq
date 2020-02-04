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

use froq\ServicerException;

/**
 * Router.
 * @package froq
 * @object  froq\Router
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Servicer
{
    /**
     * Services.
     * @var array
     */
    private array $services;

    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Gets the services property.
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services ?? [];
    }

    /**
     * Adds a service to services stack. Throws `ServicerException` if invalid service argument
     * type given, service argument is an array and no service class given or service class not
     * found.
     *
     * @param string                        $name
     * @param callable|array<string, array> $service
     * @throws froq\ServicerException
     */
    public function addService(string $name, $service): void
    {
        if (is_array($service)) {
            @ [$class, $classArgs] = $service;
            if (!$class) {
                throw new ServicerException('Service class must be provided and fully namespaced '.
                    'for array-ed service registrations');
            } elseif (!class_exists($class)) {
                throw new ServicerException('Service class "%s" not found', [$class]);
            }

            $this->services[$name] = !$classArgs ? new $class()
                : new $class(...array_values((array) $classArgs));
        } elseif (is_callable($service)) {
            $this->services[$name] = $service;
        } else {
            throw new ServicerException('Only array and callable service registrations are allowed');
        }
    }

    /**
     * Adds services to services stack.
     *
     * @param  array $services
     * @return void
     */
    public function addServices(array $services): void
    {
        foreach ($services as $name => $service) {
            $this->addService($name, $service);
        }
    }

    /**
     * Gets a service from service stack if found, otherwise returns null.
     *
     * @param  string $name
     * @return object|callable
     */
    public function getService(string $name)
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Removes a service from service stack.
     *
     * @param  string $name
     * @return object|callable
     * @return bool
     */
    public function removeService(string $name): bool
    {
        if (isset($this->services[$name])) {
            unset($this->services[$name]);
            return true;
        }
        return false;
    }

    /**
     * Checks a service if exists or not.
     *
     * @param  string $name
     * @return bool
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }
}
