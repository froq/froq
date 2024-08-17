<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

/**
 * Service registry class.
 *
 * @package froq
 * @class   froq\Servicer
 * @author  Kerem Güneş
 * @since   4.0
 */
class Servicer
{
    /** Services. */
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
     * Add a service to services stack, or throws `ServicerException` if invalid service argument
     * type given, service argument is an array and no service class given or service class not found.
     *
     * @param  string                $name
     * @param  array|object|callable $service
     * @return self
     * @throws froq\ServicerException
     */
    public function addService(string $name, array|object|callable $service): self
    {
        if (is_array($service)) {
            [$class, $classArgs, $options] = array_pad($service, 3, null);

            if (!is_string($class)) {
                throw new ServicerException(
                    'Service class must be provided as string and fully '.
                    'named for array-ed service registrations'
                );
            } elseif (!class_exists($class)) {
                throw new ServicerException(
                    'Service class %q not found',
                    $class
                );
            }

            if (is_bool($options)) {
                $options = ['once' => $options];
            }

            // Init once as default.
            $options['once'] ??= true;

            if (!empty($options['once']) && !empty($this->services[$name])) {
                // Pass, already inited and present.
            } else {
                $this->services[$name] = new $class(...(array) $classArgs);
            }
        } else {
            $this->services[$name] = $service;
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
     * @return void
     */
    public function removeService(string $name): void
    {
        unset($this->services[$name]);
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
