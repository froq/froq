<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\request\payload;

use froq\http\Request;
use froq\common\interface\Arrayable;

/**
 * Base payload class.
 *
 * @package froq\http\response\payload
 * @class   froq\http\response\payload\Payload
 * @author  Kerem Güneş
 * @since   7.3
 */
abstract class Payload implements Arrayable, \ArrayAccess, \IteratorAggregate
{
    public function __construct(
        public readonly Request $request
    )
    {}

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->get($key) !== null;
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    /**
     * @throws ReadonlyError
     */
    public function offsetSet(mixed $key, mixed $_): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @throws ReadonlyError
     */
    public function offsetUnset(mixed $key): never
    {
        throw new \ReadonlyError($this);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        return yield from $this->toArray();
    }

    /**
     * Used for "okay" states in subclasses.
     */
    protected function isTypeOkay(string ...$searches): bool
    {
        $contentType = $this->request->getContentType() ?? '';

        foreach ($searches as $search) {
            if (stristr($contentType, $search) !== false) {
                return true;
            }
        }
        return false;
    }
}
