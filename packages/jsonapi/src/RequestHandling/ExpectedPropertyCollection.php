<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

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
        return array_fill_keys($this->requiredAttributes, $this->getAttributeConstraints());
    }

    /**
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getAllowedAttributes(): array
    {
        return array_fill_keys(
            array_merge($this->requiredAttributes, $this->optionalAttributes),
            $this->getAttributeConstraints()
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
            fn (string $typeIdentifier): array => $this->getToOneRelationshipConstraints($typeIdentifier),
            array_merge($this->requiredToOneRelationships, $this->optionalToOneRelationships)
        );

        $toManyRelationships = array_map(
            fn (string $typeIdentifier): array => $this->getToManyRelationshipConstraints($typeIdentifier),
            array_merge($this->requiredToManyRelationships, $this->optionalToManyRelationships)
        );

        return array_merge($toOneRelationships, $toManyRelationships);
    }

    /**
     * @return list<Constraint>
     */
    protected function getAttributeConstraints(): array
    {
        // TODO: the JSON:API specification allows more attribute types, but those require more complex validation
        return [
            new Assert\Type(['string', 'int', 'float', 'bool']),
        ];
    }

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @return list<Constraint>
     */
    protected function getToOneRelationshipConstraints(string $typeIdentifier): array
    {
        return [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\Collection([
                ContentField::DATA => new Assert\AtLeastOneOf([
                    // applies to non-`null` to-one relationships
                    new Assert\Sequentially($this->getRelationshipConstraints($typeIdentifier)),
                    // applies to `null` to-one relationships
                    new Assert\IsNull(),
                ]),
            ], null, null, false, false),
        ];
    }

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @return list<Constraint>
     */
    protected function getToManyRelationshipConstraints(string $typeIdentifier): array
    {
        return [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\Collection([
                ContentField::DATA => [
                    // applies to to-many relationships
                    new Assert\NotNull(),
                    new Assert\Type('array'),
                    new Assert\All($this->getRelationshipConstraints($typeIdentifier)),
                ],
            ], null, null, false, false),
        ];
    }
}
