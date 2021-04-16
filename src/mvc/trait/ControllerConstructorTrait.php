<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\trait;

use froq\mvc\Controller;

/**
 * Controller Constructor Trait.
 *
 * Represents a trait entity that sets a read-only `$controller` property having constructor methods, and
 * (would/should be) used with froq\mvc\trait\ControllerTrait.
 *
 * @package froq\mvc\trait
 * @object  froq\mvc\trait\ControllerConstructorTrait
 * @author  Kerem Güneş
 * @since   5.0
 * @internal
 */
trait ControllerConstructorTrait
{
    /**
     * Constructor.
     *
     * @param froq\mvc\Controller|null $controller
     */
    public function __construct(Controller $controller = null)
    {
        // Use given or registry controller.
        $this->controller = $controller ?? registry()::get('@controller');
    }
}
