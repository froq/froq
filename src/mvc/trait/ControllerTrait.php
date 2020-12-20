<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
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
 * @author  Kerem Güneş <k-gun@mail.com>
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
    public function controller(): Controller
    {
        return $this->controller;
    }
}
