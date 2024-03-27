<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq;

/**
 * An enum class for app environment names.
 *
 * @package froq
 * @class   froq\AppEnv
 * @author  Kerem Güneş
 * @since   4.0
 */
class AppEnv extends \froq\common\object\Enum
{
    /** Environment names. */
    public const DEVELOPMENT = 'development',
                 TESTING     = 'testing',
                 STAGING     = 'staging',
                 PRODUCTION  = 'production';

    /** Environment name. */
    public readonly string $name;

    /**
     * @override
     */
    public function __construct(string $name)
    {
        $this->name = $name;

        parent::__construct($name);
    }

    /**
     * @override
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Check if environment is "development".
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->value === self::DEVELOPMENT;
    }

    /**
     * Check if environment is "testing".
     *
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->value === self::TESTING;
    }

    /**
     * Check if environment is "staging".
     *
     * @return bool
     */
    public function isStaging(): bool
    {
        return $this->value === self::STAGING;
    }

    /**
     * Check if environment is "production".
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->value === self::PRODUCTION;
    }

    /**
     * @alias isDevelopment()
     */
    public function isDev()
    {
        return $this->isDevelopment();
    }

    /**
     * @alias isTesting()
     */
    public function isTest()
    {
        return $this->isTesting();
    }

    /**
     * @alias isStaging()
     */
    public function isStage()
    {
        return $this->isStaging();
    }

    /**
     * @alias isProduction()
     */
    public function isProd()
    {
        return $this->isProduction();
    }
}
