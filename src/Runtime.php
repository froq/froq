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
 * Runtime.
 *
 * Represents a runtime entity used for measuring the execution time of the App. Also contains a
 * static method that could be used to measure executions in needed use cases.
 *
 * @package froq
 * @object  froq\Runtime
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   3.0, 4.0
 */
final class Runtime
{
    /**
     * Start.
     * @var float
     */
    private float $start = 0.00;

    /**
     * End.
     * @var float
     */
    private float $end = 0.00;

    /**
     * Total.
     * @var float
     */
    private float $total = 0.00;

    /**
     * Precision.
     * @var int
     */
    private int $precision = 4;

    /**
     * Construct.
     * @param float|null $start
     * @param float|null $end
     * @param int|null   $precision
     */
    public function __construct(float $start = null, float $end = null, int $precision = null)
    {
        $start     && $this->start($start);
        $end       && $this->end($end);
        $precision && $this->precision($precision);
    }

    /**
     * Clone.
     * @return void
     */
    public function __clone()
    {
        $this->reset();
    }

    /**
     * To string.
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Start.
     * @param  float|null $start
     * @return float
     */
    public function start(float $start = null): float
    {
        $this->start = $start ?? microtime(true);

        return $this->start;
    }

    /**
     * End.
     * @param  float|null $end
     * @return float
     */
    public function end(float $end = null): float
    {
        $this->end = $end ?? microtime(true);

        // In case start() call was forgotten, indicate to error with -1.
        if ($this->start < 0.01) {
            $this->total = -1;
        } else {
            $this->total = $this->end - $this->start;
        }

        return $this->end;
    }

    /**
     * Precision.
     * @param  int|null $end
     * @return int
     */
    public function precision(int $precision = null): int
    {
        $this->precision = $precision ?? $this->precision;

        return $this->precision;
    }

    /**
     * Total.
     * @return float
     */
    public function total(): float
    {
        return $this->total;
    }

    /**
     * Get result.
     * @return array
     */
    public function getResult(): array
    {
        return [$this->start, $this->end, $this->total];
    }

    /**
     * Print result.
     * @return void
     */
    public function printResult(): void
    {
        print $this->toString();
    }

    /**
     * Reset.
     * @return void
     */
    public function reset(): void
    {
        $this->start = $this->end = $this->total = 0.00;
    }

    /**
     * To string.
     * @param  bool $full
     * @return string
     */
    public function toString(bool $full = false): string
    {
        [$start, $end, $total, $format] = [...$this->getResult(), "%.{$this->precision}f"];

        return !$full
            ? sprintf($format, $total)
            : sprintf("start: {$format}, end: {$format}, total: {$format}", $start, $end, $total);
    }

    /**
     * Execute.
     * @param  callable $call
     * @param  int      $callLimit
     * @return froq\Runtime
     */
    public static function execute(callable $call, int $callLimit = 1): Runtime
    {
        $runtime = new Runtime();
        $runtime->start();

        for ($i = 1, $il = ($callLimit + 1); $i < $il; $i++) {
            $call($i);
        }

        $runtime->end();

        return $runtime;
    }
}
