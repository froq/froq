<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response;

use froq\file\Finder;
use XArray;

/**
 * An HTTP Status Code class with some utility methods, and used by response class.
 *
 * @package froq\http\response
 * @class   froq\http\response\Status
 * @author  Kerem Güneş
 * @since   1.0
 */
class Status extends Statuses
{
    /** Code. */
    private int $code = 0;

    /** Text (aka reason phrase). */
    private ?string $text = null;

    /**
     * Constructor.
     *
     * @param int         $code
     * @param string|null $text
     */
    public function __construct(int $code = 0, string $text = null)
    {
        $code && $this->setCode($code);
        $text && $this->setText($text);
    }

    /**
     * Set code.
     *
     * @param  int $code
     * @return void
     * @throws froq\http\response\StatusException
     */
    public function setCode(int $code): void
    {
        self::validate($code) || throw new StatusException(
            'Invalid code ' . $code
        );

        $this->code = $code;
    }

    /**
     * Get code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set text.
     *
     * @param  string|null $text
     * @return void
     */
    public function setText(string|null $text): void
    {
        $this->text = $text;
    }

    /**
     * Get text.
     *
     * @param  int $code
     * @return string|null
     */
    public function getText(): string|null
    {
        return $this->text;
    }

    /**
     * Code is 200?
     *
     * @return bool
     * @since  6.0
     */
    public function ok(): bool
    {
        return ($this->code === 200);
    }

    /**
     * Code is success code?
     *
     * @return bool
     * @since  6.0
     */
    public function isSucces(): bool
    {
        return ($this->code >= 200 && $this->code <= 299);
    }

    /**
     * @alias isError()
     */
    public function isFailure(): bool
    {
        return $this->isError();
    }

    /**
     * Code is redirect code?
     *
     * @return bool
     * @since  6.0
     */
    public function isRedirect(): bool
    {
        return ($this->code >= 300 && $this->code <= 399);
    }

    /**
     * Code is error code?
     *
     * @return bool
     * @since  6.0
     */
    public function isError(): bool
    {
        return ($this->isClientError() || $this->isServerError());
    }

    /**
     * Code is client-error code?
     *
     * @return bool
     * @since  6.0
     */
    public function isClientError(): bool
    {
        return ($this->code >= 400 && $this->code <= 499);
    }

    /**
     * Code is server-error code?
     *
     * @return bool
     * @since  6.0
     */
    public function isServerError(): bool
    {
        return ($this->code >= 500 && $this->code <= 599);
    }

    /**
     * Status allowed for a body?
     *
     * @return bool
     * @since  6.0
     */
    public function isAllowedForBody(): bool
    {
        // No contents.
        if ($this->code === 204 || $this->code === 304) {
            return false;
        }

        // Informationals.
        if ($this->code >= 99 && $this->code <= 199) {
            return false;
        }

        return true;
    }

    /**
     * Validate given status code.
     *
     * @param  int $code
     * @return bool
     */
    public static function validate(int $code): bool
    {
        // @cancel
        // Since only IANA-defined codes are there, use defined codes only.
        // return array_key_exists($code, parent::all());

        return ($code >= 100 && $code <= 599);
    }

    /**
     * Get code by text.
     *
     * @param  string $text
     * @return int|null
     */
    public static function getCodeByText(string $text): int|null
    {
        return array_find_key(parent::all(), fn($_text): bool => $_text === $text);
    }

    /**
     * Get text by code.
     *
     * @param  int $code
     * @return string|null
     */
    public static function getTextByCode(int $code): string|null
    {
        return array_find(parent::all(), fn($_, $_code): bool => $_code === $code);
    }

    /**
     * Get HTTP exceptions.
     *
     * @param  string|null $group Valids: client, server.
     * @return array<string, int>
     * @internal
     */
    public static function getHttpExceptions(string $group = null): array
    {
        static $ret;

        $ret ??= (new Finder(__DIR__ . '/../../http/exception'))
            ->xglob('/{client,server}/*', flags: GLOB_BRACE, map: false)
            ->forEach(function (string $file, int $key, XArray $ret): void {
                // Use file names as class names, transform dirsep to nssep.
                $class = preg_replace(['~.+/src/(.+)\.php~', '~/+~'], ['froq/$1', '\\'], $file);

                // Drop old values, set keys/values.
                $ret->repose($key, [$class, $class::CODE]);
            });

        if ($group) {
            $ret->filterKeys(function (string $class) use ($group): bool {
                return str_contains($class, '\\' . $group);
            });
        }

        return $ret->sort()->toArray();
    }
}
