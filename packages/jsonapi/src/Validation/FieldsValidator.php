<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function is_array;

/**
 * Validates sparse fieldset definitions that may be present in a request.
 *
 * @see https://jsonapi.org/format/#fetching-sparse-fieldsets
 */
class FieldsValidator
{
    /**
     * @var non-empty-list<Constraint>
     */
    private array $typeConstraints;

    /**
     * @var non-empty-list<Constraint>
     */
    private array $propertiesConstraints;

    public function __construct(
        protected readonly ValidatorInterface $validator
    ) {
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
                new Assert\Regex('/^('.Patterns::PROPERTY_NAME_LIST.')?$/')
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
     * This will **only** partly assert the correct format, and not the content at all.
     * I.e. it will not be counter-checked with the corresponding types and properties.
     * Use {@link self::getNonReadableProperties()} to do so.
     *
     * @return array<non-empty-string, string>
     *
     * @throws FieldsException
     */
    public function validateFormat(mixed $fieldValue): array
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
                "Invalid format used for 'fields' parameter.",
                0,
                new ValidationFailedException($fieldValue, $violations)
            );
        }

        \Webmozart\Assert\Assert::isArray($fieldValue);

        return $fieldValue;
    }

    /**
     * Counter-check the sparse fieldset definition for a specific type.
     *
     * Expects a type instance and the sparse fieldset definition for that type provided in the request.
     * The sparse fieldset definition is to be given as string and its format will be validated by the method.
     * The expected format of the string is a comma separated list of property names.
     *
     * I.e. after calling {@link validateFormat} you would loop through the result and retrieve the corresponding type
     * for each array key. The type instance and the array's value are then passed into this method.
     *
     * @param PropertyReadableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @return list<string> All property names in that the given string, that are readable in the given type.
     * This includes malformed property names like empty strings.
     */
    public function getNonReadableProperties(string $propertiesString, PropertyReadableTypeInterface $type): array
    {
        return '' === $propertiesString
            ? []
            : array_values(
                array_diff(
                    explode(',', $propertiesString),
                    $type->getReadability()->getPropertyKeys()
                )
            );
    }
}
