<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\database\entity\meta\{MetaParser, MetaException};
use froq\validation\Validation;
use Closure, ReflectionNamedType;

/**
 * Mapper class, maps properties from POST/GET data to DTO classes/instances.
 *
 * Examples:
 * ```
 * // Static use.
 * $bug = InputMapper::mapTo(BugDto::class, errors: $errors)
 *
 * // Or.
 * function saveAction() {
 *   $mapper = new InputMapper(BugDto::class)
 *
 *   $bug = $mapper->mapPost(errors: $errors)
 *   $errors && throw BadRequestException()
 *
 *   $this->repository->save((array) $bug)
 * }
 *
 * // Or.
 * function __construct(readonly InputMapper $mapper = new InputMapper(new BugDto())) {}
 * function __construct(readonly InputMapper $mapper = new InputMapper(BugDto::class)) {}
 *
 * function saveAction() {
 *   $bug = $this->mapper->mapPost(errors: $errors)
 *   $errors && throw BadRequestException()
 *
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
     * @param string|object|null  $do Target data class / object.
     * @param string|array        $source
     * @param string|Closure|null $apply
     */
    public function __construct(
        public readonly string|object $do,
        public readonly string|array $source = 'POST',
        public readonly string|Closure|null $apply = null
    ) {}

    /**
     * Map from POST/GET to self DTO.
     *
     * @param  string|array|null    $source
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     */
    public function map(string|array $source = null, string|callable $apply = null, array &$errors = null): object|null
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
        return self::mapTo($this->do, 'POST', $apply ?? $this->apply, $errors);
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
        return self::mapTo($this->do, 'GET', $apply ?? $this->apply, $errors);
    }

    /**
     * Map from POST/GET to self DTO.
     *
     * @param  string|object        $do
     * @param  string|array|null    $source
     * @param  string|callable|null $apply
     * @param  array|null           &$errors
     * @return object|null
     * @throws UndefinedClassError|UnimplementedError|Error
     */
    public static function mapTo(string|object $do, string|array $source = 'POST', string|callable $apply = null,
        array &$errors = null): object|null
    {
        try {
            $meta = MetaParser::parseClassMeta($do);
        } catch (\Throwable $e) {
            if ($e instanceof MetaException && $e->getCause() instanceof \ReflectionException) {
                $e = new \UndefinedClassError(get_class_name($do, escape: true));
            }
            throw $e;
        }

        $ref = $meta->getReflection();

        if (is_string($do)) {
            $do = $ref->getConstructor()?->getNumberOfRequiredParameters()
                ? $ref->newInstanceWithoutConstructor()
                : $ref->newInstance();
        }

        // Try using class meta properties.
        foreach ($meta->getPropertyMetas() as $pmeta) {
            $props[] = $pmeta->getReflection();
        }

        $props ??= $ref->getProperties();

        if (!$props) {
            throw new \UnimplementedError(
                'Class %k must define properties to be mapped',
                get_class_name($do, escape: true),
            );
        }

        if (is_string($source)) {
            $data = match (strtoupper($source)) {
                default => throw new \Error('Invalid source, valids: POST, GET'),
                'POST' => app()->request->post(map: $apply),
                'GET' => app()->request->get(map: $apply),
            };
        } else {
            $data = $apply ? map($source, $apply, recursive: true) : $source;
            unset($source);
        }

        $keys = []; $vars = [];
        foreach ($props as $prop) {
            $keys[] = $prop->name;

            if ($prop->isInitialized($do)) {
                $vars[$prop->name] = $prop->getValue($do);
            }

            // Re-set with default if value is null.
            $vars[$prop->name] ??= $prop->getDefaultValue();
        }

        // Overwrite object vars with request data,
        // so use object vars as defaults if given.
        foreach ($keys as $key) {
            $temp[$key] = null;
            if (isset($data[$key])) {
                $temp[$key] = $data[$key];
            } elseif (isset($vars[$key])) {
                $temp[$key] = $vars[$key];
            }
        }

        [$data, $temp, $vars] = [$temp, null, null];

        $okay = true;

        // Validation.
        if ($meta->getOption('validate')) {
            $rules = [];

            foreach ($meta->getPropertyMetas() as $pmeta) {
                $field = $pmeta->getShortName();
                $rules[$field] = $pmeta->getData();
            }

            // No rules will cause ValidationException.
            $okay = (new Validation($rules))->validate($data, $errors);
        }
        // Type castings.
        else {
            foreach ($props as $prop) {
                if (isset($data[$prop->name]) && $prop->hasType()) {
                    $type = $prop->getType();
                    if ($type instanceof ReflectionNamedType
                        && preg_test('~^(int|float|string|bool)$~', $type->getName())) {
                        settype($data[$prop->name], $type->getName());
                    }
                }
            }
        }

        // Set/re-set properties.
        if ($okay) foreach ($props as $prop) {
            $prop->setValue($do, $data[$prop->name]);
        }

        return $okay ? $do : null;
    }
}
