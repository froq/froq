<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

/**
 * An enum class, carries App environment names.
 *
 * @package froq
 * @class   froq\Env
 * @author  Kerem Güneş
 * @since   4.0
 */
class Env extends \froq\common\object\Enum
{
    /** Names. */
    public const DEVELOPMENT = 'development',
                 TESTING     = 'testing',
                 STAGING     = 'staging',
                 PRODUCTION  = 'production';
}
