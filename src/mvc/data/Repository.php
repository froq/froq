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
 * Repository.
 *
 * Represents an entity which is extended by producers/providers or other data/database related classes.
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\Repository
 * @author  Kerem Güneş
 * @since   5.0
 */
class Repository
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

        // Not all controllers use models.
        if ($model = $this->controller->getModel()) {
            $this->model = $model;
        }
    }
}
