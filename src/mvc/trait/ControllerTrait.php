<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\trait;

use froq\mvc\Controller;

/**
 * Controller Trait.
 *
 * Represents a trait entity that holds a read-only `$controller` property and its getter method.
 *
 * @package froq\mvc\trait
 * @object  froq\mvc\trait\ControllerTrait
 * @author  Kerem Güneş
 * @since   5.0
 * @internal
 */
trait ControllerTrait
{
    /** @var froq\mvc\Controller */
    protected Controller $controller;

    /**
     * Get controller property.
     *
     * @return froq\mvc\Controller
     */
    public final function controller(): Controller
    {
        return $this->controller;
    }

    /**
     * Export supclass vars for subclasses.
     *
     * @param  array|string $vars
     * @return array
     */
    public final function exportControllerStuff(array|string $vars = null): array
    {
        // Add most used defaults (controller & model).
        $ret = [$this->controller, $this->controller->getModel()];

        // Gather optional vars (that also may be dynamic vars).
        if ($vars != null) {
            foreach ((array) $vars as $var) {
                $ret[] = $this->controller->{$var} ?? null;
            }
        }

        return $ret;
    }
}
