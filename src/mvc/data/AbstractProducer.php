<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ModelTrait};

/**
 * Abstract Producer.
 *
 * Represents an abstract entity which is extended by producers those are responsable (basically) data preparation
 * (eg: validation, sanitization) for saving data to the persistence layer (eg. database via controller's models).
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\AbstractProducer
 * @author  Kerem Güneş
 * @since   5.0
 */
abstract class AbstractProducer
{
    /** @see froq\mvc\trait\ControllerTrait */
    /** @see froq\mvc\trait\ModelTrait */
    use ControllerTrait, ModelTrait;

    /**
     * Constructor.
     *
     * @param froq\mvc\Controller|null $controller
     */
    public function __construct(Controller $controller = null)
    {
        // Use given or registry controller.
        $this->controller = $controller ?? registry()::get('@controller');

        // Not all controllers use a model.
        if ($model = $this->controller->getModel()) {
            $this->model = $this->controller->getModel();
        }
    }
}
