<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

use froq\ServicerException;

/**
 * Servicer.
 *
 * @package froq
 * @object  froq\Servicer
 * @author  Kerem Güneş
 * @since   4.0
 */
final class Servicer
{
    /** @var array */
    private array $services = [];

    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Get services.
     *
     * @return array
     */
    public function services(): array
    {
        return $this->services;
    }

    /**
     * Add a service to services stack, or throws `ServicerException` if invalid service argument
     * type given, service argument is an array and no service class given or service class not found.
     *
     * @param  string                $name
     * @param  object|callable|array $service
     * @return self
     * @throws froq\ServicerException
     */
    public function addService(string $name, object|callable|array $service): self
    {
        if (is_array($service)) {
            [$class, $classArgs] = array_select($service, [0, 1]);

            if ($class == null) {
                throw new ServicerException('Service class must be provided and fully namespaced for'
                    . ' array-ed service registrations');
            } elseif (!class_exists($class)) {
                throw new ServicerException('Service class `%s` not found', $class);
            }

            $this->services[$name] = !$classArgs ? new $class() : new $class(...(array) $classArgs);
        } else {
            $this->services[$name] = $service;
        }

        return $this;
    }

    /**
     * Add services to services stack.
     *
     * @param  array $services
     * @return self
     */
    public function addServices(array $services): self
    {
        foreach ($services as $name => $service) {
            $this->addService($name, $service);
        }

        return $this;
    }

    /**
     * Get a service from service stack if found, otherwise returns null.
     *
     * @param  string $name
     * @return object|callable|null
     */
    public function getService(string $name): object|callable|null
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Remove a service from service stack.
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
     * Check whether a service exists.
     *
     * @param  string $name
     * @return bool
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }
}
