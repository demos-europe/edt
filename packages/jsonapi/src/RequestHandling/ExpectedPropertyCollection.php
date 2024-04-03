<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\ContentField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class ExpectedPropertyCollection implements ExpectedPropertyCollectionInterface
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
        protected bool $idRequired,
        protected array $requiredAttributes,
        protected array $requiredToOneRelationships,
        protected array $requiredToManyRelationships,
        protected bool $idOptional,
        protected array $optionalAttributes,
        protected array $optionalToOneRelationships,
        protected array $optionalToManyRelationships,
    ) {}

    public function getRequiredAttributes(int $validationLevelDepth, bool $allowAnythingBelowDepth): array
    {
        return array_fill_keys($this->requiredAttributes, $this->getConstraintsForAttribute($validationLevelDepth, $allowAnythingBelowDepth));
    }

    public function getAllowedAttributes(int $validationLevelDepth, bool $allowAnythingBelowDepth): array
    {
        return array_fill_keys(
            array_merge($this->requiredAttributes, $this->optionalAttributes),
            $this->getConstraintsForAttribute($validationLevelDepth, $allowAnythingBelowDepth)
        );
    }

    public function getRequiredRelationships(): array
    {
        return array_merge(
            // TODO: probably empty array because already set in `getAllowedRelationships`, but can this return be adjusted then?
            array_map(static fn (string $typeIdentifier): array => [], $this->requiredToOneRelationships),
            array_map(static fn (string $typeIdentifier): array => [], $this->requiredToManyRelationships)
        );
    }

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
     * Ensures attributes are either a primitive type, null or (optionally) an array.
     *
     * @param int<0, 8192> $validationLevelDepth The number of levels for which the returned constraint will ensure valid attribute values.
     *                                           If `1`, the returned constraint will ensure that the attribute value itself is
     *                                           a primitive type, `null` or an array. The higher the depth value, the more validation is
     *                                           done for nested arrays.
     * @param bool $allowAnythingBelowDepth Determines if arrays are allowed at all below the validation depth. E.g. if
     *                                      this parameter is set to `false` and the depth parameter is set to `1`,
     *                                      then an attribute value is allowed to be an array, but such array can not
     *                                      contain other arrays, but primitive types or `null` instead only. If set to
     *                                      `true` and `1`, then an attribute value that is an array may contain anything.
     *
     * @return list<Constraint>
     */
    protected function getConstraintsForAttribute(int $validationLevelDepth, bool $allowAnythingBelowDepth): array
    {
        $lowestLevelAllowedTypes = ['string', 'int', 'float', 'bool'];
        if ($allowAnythingBelowDepth) {
            $lowestLevelAllowedTypes[] = 'array';
        }

        $resultConstraints = [new Assert\Type($lowestLevelAllowedTypes)];
        for ($i = 0; $i < $validationLevelDepth; $i++) {
            $resultConstraints = $this->createAttributeConstraintLevel($resultConstraints);
        }

        return $resultConstraints;
    }

    /**
     * @param list<Constraint> $lowerLevel
     *
     * @return list<Constraint>
     */
    protected function createAttributeConstraintLevel(array $lowerLevel): array
    {
        return [
            new Assert\AtLeastOneOf([
                // primitive values are always valid (null is implicitly allowed)
                new Assert\Type(['string', 'int', 'float', 'bool'], 'If not null or array, attributes must be of type {{ type }}.'),
                // if the type is an array, then it must contain only non-null strings
                new class($lowerLevel) extends Assert\Compound {
                    /**
                     * @param list<Constraint> $lowerLevel
                     */
                    public function __construct(protected readonly array $lowerLevel)
                    {
                        parent::__construct();
                    }

                    /**
                     * @inheritDoc
                     */
                    protected function getConstraints(mixed $options): array
                    {
                        return [
                            new Assert\Type('array'),
                            new Assert\All($this->lowerLevel),
                        ];
                    }
                }
            ])
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

    public function isIdAllowed(): bool
    {
        return $this->idOptional || $this->idRequired;
    }

    public function isIdRequired(): bool
    {
        return $this->idRequired;
    }

    public function getIdConstraints(): array
    {
        // TODO: allow to add constraints in this class?
        return [];
    }
}
