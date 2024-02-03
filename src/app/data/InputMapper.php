<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\database\entity\meta\{MetaParser, MetaException};
use froq\validation\Validation;

/**
 * Mapper class, maps properties from POST/GET data to DTO classes/instances.
 *
 * Examples:
 * ```
 * // Static use case.
 * $bug = InputMapper::mapTo(BugDto::class, errors: $errors)
 *
 * // Or.
 * function saveAction() {
 *   $mapper = new InputMapper(BugDto::class)
 *   $bug = $mapper->mapPost(errors: $errors)
 *   if ($errors) throw BadRequestException()
 *   $this->repository->save((array) $bug)
 * }
 *
 * // Or.
 * function __construct(readonly InputMapper $mapper = new InputMapper(new BugDto())) {}
 * function __construct(readonly InputMapper $mapper = new InputMapper(BugDto::class)) {}
 *
 * function saveAction() {
 *   $bug = $this->mapper->mapPost(errors: $errors)
 *   if ($errors) throw BadRequestException()
 *   $this->repository->save((array) $bug)
 * }
 * ```
 *
 * @package froq\app\data
 * @class   froq\app\data\InputMapper
 * @author  Kerem Güneş
 * @since   6.0
 */
class InputMapper
{
    /**
     * Constructor.
     *
     * @param string|object|null $do
     * @param string             $source
     * @param string|null        $apply
     */
    public function __construct(
        public readonly string|object $do,
        public readonly string $source = 'post',
        public readonly string|null $apply = null
    ) {}

    /**
     * Map from POST/GET to self DTO.
     *
     * @param  string|null          $source
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     */
    public function map(string $source = null, string|callable $apply = null, array &$errors = null): object|null
    {
        return self::mapTo($this->do, $source ?? $this->source, $apply ?? $this->apply, $errors);
    }

    /**
     * Map from POST to self DTO.
     *
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     */
    public function mapPost(string|callable $apply = null, array &$errors = null): object|null
    {
        return self::mapTo($this->do, 'post', $apply ?? $this->apply, $errors);
    }

    /**
     * Map from GET to self DTO.
     *
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     */
    public function mapGet(string|callable $apply = null, array &$errors = null): object|null
    {
        return self::mapTo($this->do, 'get', $apply ?? $this->apply, $errors);
    }

    /**
     * Map from POST/GET to self DTO.
     *
     * @param  string||object       $do
     * @param  string|null          $source
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     */
    public static function mapTo(string|object $do, string $source = 'post', string|callable $apply = null,
        array &$errors = null): object|null
    {
        try {
            $meta = MetaParser::parseClassMeta($do);
        } catch (\Throwable $e) {
            if ($e instanceof MetaException &&
                $e->getCause() instanceof \ReflectionException) {
                $e = new \UndefinedClassError(get_class_name($do, escape: true));
            }

            throw $e;
        }

        $ref = $meta->getReflection();

        // Promoted constructor parameters.
        if (is_string($do) && $ref->isReadOnly()) {
            $do = $ref->newInstanceWithoutConstructor();
        } elseif (is_string($do)) {
            $do = $ref->newInstance();
        }

        $props = $meta->getPropertyMetas() ?: $ref->getProperties();

        if (!$props) {
            throw new \UnimplementedError(
                'Class %k must define properties to be populated',
                get_class_name($do, escape: true),
            );
        }

        $data = match (strtolower($source)) {
            default => throw new \Error('Invalid source, valids: post, get'),
            'post' => app()->request->post(map: $apply),
            'get' => app()->request->get(map: $apply),
        };
        $okay = true;

        if ($meta->getOption('validate')) {
            $pmetas = $meta->getPropertyMetas();
            $rules = [];

            foreach ($pmetas as $pmeta) {
                $field = $pmeta->getShortName();
                $rules[$field] = $pmeta->getData();
            }

            // No rules will cause ValidationException.
            $okay = (new Validation($rules))->validate($data, $errors);
        }

        if ($pmetas ??= $meta->getPropertyMetas()) {
            foreach ($pmetas as $pmeta) {
                $field = $pmeta->getShortName();
                $pmeta->getReflection()->setValue($do, $data[$field]);
            }
        } elseif ($props ??= $ref->getProperties()) {
            foreach ($props as $prop) {
                $prop->setValue($do, $data[$prop->name]);
            }
        }

        return $okay ? $do : null;
    }
}
