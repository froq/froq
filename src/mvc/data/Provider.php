<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\mvc\data;

/**
 * Provider.
 *
 * Represents a class entity which is extended by providers those are responsable (basically) data preparation only
 * (eg: mapping, casting) for the presentation layer getting records from the persistence layer (eg. database via
 * controller models).
 *
 * @package froq\mvc\data
 * @object  froq\mvc\data\Provider
 * @author  Kerem Güneş
 * @since   5.0
 */
class Provider extends Repository
{}
