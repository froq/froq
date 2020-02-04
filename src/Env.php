<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq;

/**
 * Env.
 *
 * Represents an environment entity, used for detecting application environment that decide
 * such stuff which database would be connected to, etc. More information could be fould at
 * https://en.wikipedia.org/wiki/Deployment_environment.
 *
 * @package froq
 * @object  froq\Env
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class Env
{
    /**
     * Names.
     * @const string
     */
    public const DEV        = 'dev',
                 TEST       = 'test',
                 STAGE      = 'stage',
                 PRODUCTION = 'production';

    /**
     * Name.
     * @var string
     */
    private string $name;

    /**
     * Constructor.
     * @param string $name
     */
    public function __construct(string $name = self::DEV)
    {
        $this->name = $name;
    }

    /**
     * Set name.
     * @param  string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is dev.
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->name == self::DEV;
    }

    /**
     * Is test.
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->name == self::TEST;
    }

    /**
     * Is stage.
     * @return bool
     */
    public function isStage(): bool
    {
        return $this->name == self::STAGE;
    }

    /**
     * Is production.
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->name == self::PRODUCTION;
    }
}
