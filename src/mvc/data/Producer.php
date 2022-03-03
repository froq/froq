<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

/**
 * Producer.
 *
 * A class, extended by producers those are responsable (basically) data preparation only
 * (eg: validation, sanitization) for saving data to the persistence layer (eg. database
 * via controller models).
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\Producer
 * @author  Kerem Güneş
 * @since   5.0
 */
class Producer extends Repository
{}
