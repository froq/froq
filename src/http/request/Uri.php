<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request;

/**
 * URI class, used by request class.
 *
 * @package froq\http\request
 * @class   froq\http\request\Uri
 * @author  Kerem Güneş
 * @since   1.0
 */
class Uri extends \Url
{
    /** Segments instance. */
    public readonly Segments $segments;

    /**
     * Constructor.
     *
     * @param  array|string $source
     * @throws froq\http\request\UriException
     */
    public function __construct(array|string $source)
    {
        try {
            parent::__construct($source);
        } catch (\Throwable $e) {
            throw new UriException($e);
        }
    }

    /**
     * Get a segment param.
     *
     * @param  int|string  $key
     * @param  string|null $default
     * @return string|null
     * @throws froq\http\request\UriException
     */
    public function segment(int|string $key, string $default = null): string|null
    {
        isset($this->segments) || throw new UriException(
            'Property $segments not set yet [method generateSegments() not called]'
        );

        return $this->segments->get($key, $default);
    }

    /**
     * Get a segment param or Segments object.
     *
     * @param  array<int|string>|null $keys
     * @param  array<string>|null     $defaults
     * @return array<string>froq\http\request\Segments
     * @throws froq\http\request\UriException
     */
    public function segments(array $keys = null, array $defaults = null): array|Segments
    {
        isset($this->segments) || throw new UriException(
            'Property $segments not set yet [method generateSegments() not called]'
        );

        if ($keys === null) {
            return $this->segments;
        }

        $values = [];
        foreach ($keys as $i => $key) {
            $values[] = $this->segments->get($key, $defaults[$i] ?? null);
        }

        return $values;
    }

    /**
     * Generate segments.
     *
     * @param  string|null $root
     * @return void
     * @throws froq\http\request\UriException
     * @internal
     */
    public function generateSegments(string $root = null): void
    {
        isset($this->segments) && throw new UriException(
            'Cannot re-generate segments'
        );

        $this->segments = self::parseSegments((string) $this->getPath(), $root);
    }

    /**
     * Parse segments.
     *
     * @param  string      $path
     * @param  string|null $root
     * @return froq\http\request\Segments
     * @throws froq\http\request\UriException
     * @since  6.0
     */
    public static function parseSegments(string $path, string $root = null): Segments
    {
        $path         = rawurldecode($path);
        $segments     = [];
        $segmentsRoot = Segments::ROOT;

        if ($path !== '' && $path !== $segmentsRoot) {
            // Drop root if exists.
            if ($root !== '' && $root !== $segmentsRoot) {
                $root = '/' . trim($root, '/');

                // Prevent invalid generate action.
                if (!str_starts_with($path, $root)) {
                    throw new UriException('Path %q has no root %q', [$path, $root]);
                }

                // Drop root from path.
                $path = substr($path, strlen($root));

                // Update segments root.
                $segmentsRoot = $root;
            }

            $segments = preg_split('~/+~', $path, flags: 1);

            // In any case.
            if ($segments === false) {
                throw new UriException('Cannot generate segments [error: @error]');
            }
        }

        return Segments::fromArray($segments, $segmentsRoot);
    }
}
