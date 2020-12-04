<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq;

use froq\ServicerException;

/**
 * Servicer.
 *
 * @package froq
 * @object  froq\Servicer
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Servicer
{
    /**
     * Services.
     * @var array
     */
    private array $services = [];

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
        return $this->services;
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
            if ($class == null) {
                throw new ServicerException('Service class must be provided and fully namespaced '
                    . 'for array-ed service registrations');
            } elseif (!class_exists($class)) {
                throw new ServicerException("Service class '%s' not found", $class);
            }

            $this->services[$name] = !$classArgs ? new $class()
                : new $class(...array_values((array) $classArgs));
        } elseif (is_object($service) || is_callable($service)) {
            $this->services[$name] = $service;
        } else {
            throw new ServicerException('Only array, object and callable service registrations are allowed');
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
     * @return object|callable|null
     */
    public function getService(string $name)
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Removes a service from service stack.
     *
     * @param  string $name
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
     * Checks whether a service exists.
     *
     * @param  string $name
     * @return bool
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }
}
