<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\trait;

use froq\mvc\Model;

/**
 * Model Trait.
 *
 * A trait, provides `$model` property and its getter method.
 *
 * @package froq\mvc\trait
 * @object  froq\mvc\trait\ModelTrait
 * @author  Kerem Güneş
 * @since   5.0
 * @internal
 */
trait ModelTrait
{
    /** @var froq\mvc\Model */
    protected Model $model;

    /**
     * Get model property.
     *
     * @return froq\mvc\Model
     */
    public final function model(): Model
    {
        return $this->model;
    }
}
