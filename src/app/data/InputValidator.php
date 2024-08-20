<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app\data;

use froq\validation\Validation;

/**
 * Validator class, validates data of given DTO instance & fills `$errors` byref
 * on validate process if validation fails.
 *
 * @package froq\app\data
 * @class   froq\app\data\InputValidator
 * @author  Kerem Güneş
 * @since   6.0
 * @internal
 */
class InputValidator
{
    /**
     * Constructor.
     *
     * @param object $do Source data object.
     */
    public function __construct(
        public readonly object $do
    ) {}

    /**
     * Proxy method for InputCollector.collect() method.
     *
     * @return array
     */
    public function collect(): array
    {
        return (new InputCollector($this->do))->collect();
    }

    /**
     * Validate DTO data.
     *
     * Note: To use this method, `validations()` method must be defined in DTO subclass.
     *
     * @param  array      &$data      Data to be validated.
     * @param  array|null &$errors    Generated by Validation class on fails.
     * @param  array|null  $options   Validation options, eg. "throwErrors" etc.
     * @param  array       $arguments Call-time arguments for DTO's validations() method, in case.
     * @return bool
     * @throws UndefinedMethodError
     */
    public function validate(array &$data, array &$errors = null, array $options = null, array $arguments = []): bool
    {
        if (!method_exists($this->do, 'validations')) {
            $error = new \UndefinedMethodError($this->do, 'validations');
            $error->setMessage(
                'Class %k must define %k method to be validated',
                get_class_name($this->do, escape: true), 'validations()'
            );

            throw $error;
        }

        $rules = $this->do->validations(...$arguments);

        return (new Validation($rules, $options))->validate($data, $errors);
    }
}
