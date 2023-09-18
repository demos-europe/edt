<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function is_array;

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
     * @param TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @return list<string>
     */
    public function getNonReadableProperties(string $propertiesString, TransferableTypeInterface $type): array
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
