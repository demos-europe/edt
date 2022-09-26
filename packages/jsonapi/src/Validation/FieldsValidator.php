<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\Utilities\TypeAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function is_array;

class FieldsValidator
{
    /**
     * @var TypeAccessor
     */
    private $typeAccessor;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var non-empty-list<Constraint>
     */
    private $typeConstraints;

    /**
     * @var non-empty-list<Constraint>
     */
    private $propertiesConstraints;

    public function __construct(TypeAccessor $typeAccessor, ValidatorInterface $validator)
    {
        $this->typeAccessor = $typeAccessor;
        $this->validator = $validator;
        $this->typeConstraints = [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\All([
                new Assert\Type('string'),
                new Assert\NotBlank(null, null, false, 'trim')
            ]),
        ];
        $this->propertiesConstraints = [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\All([
                new Assert\Type('string'),
                new Assert\NotNull(),
                // A comma separated list of property names.
                new Assert\Regex('/^('.Patterns::PROPERTY_NAME.'(,'.Patterns::PROPERTY_NAME.')*)?$/')
            ]),
        ];
    }

    /**
     * Validates the format of the given fieldset array.
     *
     * Its keys must be strings that should correspond to a known resource type.
     *
     * Its values must be a comma-separated list of properties that should exist in that type.
     *
     * @param mixed $fieldValue
     *
     * @return array<non-empty-string, string>
     *
     * @throws FieldsException
     */
    public function validateFormat($fieldValue): array
    {
        $violations = $this->validator->validate($fieldValue, $this->propertiesConstraints);
        if (is_array($fieldValue)) {
            $typeViolations = $this->validator->validate(
                array_keys($fieldValue),
                $this->typeConstraints
            );
            $violations->addAll($typeViolations);
        }

        if (0 !== $violations->count()) {
            throw new FieldsException(
                'Invalid format used for \'fields\' parameter.',
                0,
                new ValidationFailedException($fieldValue, $violations)
            );
        }

        return $fieldValue;
    }

    /**
     * @return list<string>
     */
    public function getNonReadableProperties(string $propertiesString, ReadableTypeInterface $type): array
    {
        if ('' === $propertiesString) {
            return [];
        }

        $requestedProperties = explode(',', $propertiesString);
        $readableProperties = $this->typeAccessor->getAccessibleReadableProperties($type);
        $readablePropertyNames = array_keys($readableProperties);

        return array_values(array_diff($requestedProperties, $readablePropertyNames));
    }
}
