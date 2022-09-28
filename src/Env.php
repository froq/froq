<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq;

/**
 * An enum class, carries App environment names.
 *
 * @package froq
 * @object  froq\Env
 * @author  Kerem Güneş
 * @since   4.0
 */
final class Env extends \froq\common\object\Enum
{
    /**
     * Names.
     * @const string
     */
    public const DEVELOPMENT = 'development',
                 TESTING     = 'testing',
                 STAGING     = 'staging',
                 PRODUCTION  = 'production';
}
