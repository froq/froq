<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq;

use froq\common\objects\Enum;

/**
 * Env.
 *
 * Represents an enum entity holding App environment types.
 *
 * @package froq
 * @object  froq\Env
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Env extends Enum
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
