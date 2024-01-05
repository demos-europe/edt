<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\ContentField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class ExpectedPropertyCollection
{
    use RequestConstraintTrait;

    /**
     * @param list<non-empty-string> $requiredAttributes list of property names
     * @param array<non-empty-string, non-empty-string> $requiredToOneRelationships
     * @param array<non-empty-string, non-empty-string> $requiredToManyRelationships
     * @param list<non-empty-string> $optionalAttributes list of property names
     * @param array<non-empty-string, non-empty-string> $optionalToOneRelationships
     * @param array<non-empty-string, non-empty-string> $optionalToManyRelationships
     */
    public function __construct(
        protected array $requiredAttributes,
        protected array $requiredToOneRelationships,
        protected array $requiredToManyRelationships,
        protected array $optionalAttributes,
        protected array $optionalToOneRelationships,
        protected array $optionalToManyRelationships,
    ) {}

    /**
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getRequiredAttributes(): array
    {
        return array_fill_keys($this->requiredAttributes, $this->getConstraintsForAttribute());
    }

    /**
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getAllowedAttributes(): array
    {
        return array_fill_keys(
            array_merge($this->requiredAttributes, $this->optionalAttributes),
            $this->getConstraintsForAttribute()
        );
    }

    /**
     * The list of constraints is likely empty, as they are already present in
     * {@link getAllowedRelationships} for all relationships and not just the required ones.
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getRequiredRelationships(): array
    {
        return array_merge(
            array_map(static fn (string $typeIdentifier): array => [], $this->requiredToOneRelationships),
            array_map(static fn (string $typeIdentifier): array => [], $this->requiredToManyRelationships)
        );
    }

    /**
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getAllowedRelationships(): array
    {
        $toOneRelationships = array_map(
            fn (string $typeIdentifier): array => $this->getConstraintsForToOneRelationship($typeIdentifier),
            array_merge($this->requiredToOneRelationships, $this->optionalToOneRelationships)
        );

        $toManyRelationships = array_map(
            fn (string $typeIdentifier): array => $this->getConstraintsForToManyRelationship($typeIdentifier),
            array_merge($this->requiredToManyRelationships, $this->optionalToManyRelationships)
        );

        return array_merge($toOneRelationships, $toManyRelationships);
    }

    /**
     * @return list<Constraint>
     */
    protected function getConstraintsForAttribute(): array
    {
        return [
            new Assert\AtLeastOneOf([
                // primitive values are always valid
                new Assert\Type(['string', 'int', 'float', 'bool'], 'If not null, attributes must be of type {{ type }}.'),
                // if the type is an array, then it must contain only non-null strings
                new class extends Assert\Compound {
                    /**
                     * @inheritDoc
                     */
                    protected function getConstraints(mixed $options): array
                    {
                        return [
                            new Assert\Type('array'),
                            new Assert\All([
                                new Assert\Type('string'),
                                new Assert\NotNull(),
                            ]),
                        ];
                    }
                }
                // TODO: the JSON:API specification may allow more attribute types, especially regarding `array`, but those require more complex validation
            ]),
        ];
    }

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @return list<Constraint>
     */
    protected function getConstraintsForToOneRelationship(string $typeIdentifier): array
    {
        return [
            new Assert\NotNull(),
            new Assert\Type('array'),
            $this->getCollectionConstraintFactory()->exactMatch('to-one relationship references', [
                ContentField::DATA => [
                    new Assert\AtLeastOneOf([
                        // applies to non-`null` to-one relationships
                        new Assert\Sequentially($this->getConstraintsForRelationship($typeIdentifier)),
                        // applies to `null` to-one relationships
                        new Assert\IsNull(),
                    ])
                ],
            ]),
        ];
    }

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @return list<Constraint>
     */
    protected function getConstraintsForToManyRelationship(string $typeIdentifier): array
    {
        return [
            new Assert\NotNull(),
            new Assert\Type('array'),
            $this->getCollectionConstraintFactory()->exactMatch('to-many relationship references', [
                ContentField::DATA => [
                    // applies to to-many relationships
                    new Assert\NotNull(),
                    new Assert\Type('array'),
                    new Assert\All($this->getConstraintsForRelationship($typeIdentifier)),
                ],
            ]),
        ];
    }
}
