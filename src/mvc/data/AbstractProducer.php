<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

use froq\mvc\Controller;
use froq\mvc\trait\{ControllerTrait, ControllerConstructorTrait};

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
    /** @see froq\mvc\trait\ControllerConstructorTrait */
    use ControllerTrait, ControllerConstructorTrait;
}
