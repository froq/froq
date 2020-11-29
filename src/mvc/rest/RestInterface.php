<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\mvc\rest;

/**
 * Rest Interface.
 *
 * Represents a interface entity that can be implemented in RESTful controllers.
 *
 * Example for a parent index(): `if this instanceof RestInterface: return this.rest(...params)`.
 *
 * @package froq\mvc\rest
 * @object  froq\mvc\rest\RestInterface
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.9
 */
interface RestInterface
{}
