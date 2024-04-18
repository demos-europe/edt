<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\ContentField;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;

class RequestConstraintFactory
{
    use RequestConstraintTrait;

    private const ROOT_DATA_CONTEXT = 'the root `data` field';
    private const ATTRIBUTES_CONTEXT = 'the list of attributes';
    private const RELATIONSHIPS_CONTEXT = 'the list of relationships';

    /**
     * @param int<0, 8192> $attributeValidationDepth
     */
    public function __construct(
        protected readonly int $attributeValidationDepth,
        protected readonly bool $attributeAllowAnythingBelowValidationDepth
    ) {}

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return list<Constraint>
     */
    public function getBodyConstraints(
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollectionInterface $expectedProperties
    ): array {
        $allowedAttributes = $expectedProperties->getAllowedAttributes($this->attributeValidationDepth, $this->attributeAllowAnythingBelowValidationDepth);
        $requiredAttributes = $expectedProperties->getRequiredAttributes($this->attributeValidationDepth, $this->attributeAllowAnythingBelowValidationDepth);
        $requiredRelationships = $expectedProperties->getRequiredRelationships();
        $attributeConstraints = $this->getAttributeConstraints($allowedAttributes, $requiredAttributes);
        $relationshipConstraints = $this->getRelationshipConstraints($expectedProperties->getAllowedRelationships(), $requiredRelationships);


        $allowedFields = [
            ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier),
            ContentField::ATTRIBUTES => $attributeConstraints,
            ContentField::RELATIONSHIPS => $relationshipConstraints,
        ];

        // If the ID was given in the URL, we're dealing with a resource update request and the ID is always *allowed* and *required* in the request body.
        // If it is not given in the URL, we're dealing with a resource creation request and *may require* the ID depending on the configuration.
        $idMustBePresent = null !== $urlId || $expectedProperties->isIdRequired();

        // If ID is required, or just optionally allowed by the configuration, we add it to the allowed fields with its corresponding constraints.
        if ($idMustBePresent || $expectedProperties->isIdAllowed()) {
            $allowedFields[ContentField::ID] = array_merge($this->getIdBaseConstraints($urlId), $expectedProperties->getIdConstraints());
        }

        $outerDataConstraints = [
            // validate attributes and relationships
            $this->getCollectionConstraintFactory()->noExtra(self::ROOT_DATA_CONTEXT, $allowedFields),
            // validate `type` field
            $this->getCollectionConstraintFactory()->noMissing(self::ROOT_DATA_CONTEXT, [
                ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier)
            ]),
        ];

        // If the ID is not just optional, but required, we add it as such
        if ($idMustBePresent) {
            $outerDataConstraints[] = $this->getCollectionConstraintFactory()->noMissing('update or creation requests', [
                // no need to set the attribute constraints here, as they were already set above
                ContentField::ID => [],
            ]);
        }

        if ([] !== $requiredAttributes) {
            // If there are required attributes, then the field `attributes` must be present
            $outerDataConstraints[] = $this->getCollectionConstraintFactory()->noMissing(self::ROOT_DATA_CONTEXT, [
                // no need to set the attribute constraints here, as they were already set above
                ContentField::ATTRIBUTES => [],
            ]);
        }

        if ([] !== $requiredRelationships) {
            // If there are required relationships, then the field `relationships` must be present
            $outerDataConstraints[] = $this->getCollectionConstraintFactory()->noMissing(self::ROOT_DATA_CONTEXT, [
                // no need to set the relationship constraints here, as they were already set above
                ContentField::RELATIONSHIPS => [],
            ]);
        }

        return [
            $this->getCollectionConstraintFactory()->exactMatch('the root level', [
                ContentField::DATA => $outerDataConstraints
            ]),
        ];
    }

    /**
     * @param array<non-empty-string, list<Constraint>> $allowedAttributes
     * @param array<non-empty-string, list<Constraint>> $requiredAttributes
     *
     * @return list<Constraint>
     */
    protected function getAttributeConstraints(array $allowedAttributes, array $requiredAttributes): array
    {
        $attributeConstraints = [
            // validate request attributes are allowed and valid
            $this->getCollectionConstraintFactory()->noExtra(self::ATTRIBUTES_CONTEXT, $allowedAttributes),
        ];

        // only create a validation for required attributes if there are any required
        // quick-fix for https://github.com/symfony/symfony/pull/53383
        if ([] !== $requiredAttributes) {
            // validate required attributes are present
            $attributeConstraints[] = $this->getCollectionConstraintFactory()->noMissing(self::ATTRIBUTES_CONTEXT, $requiredAttributes);
        }

        return $attributeConstraints;
    }

    /**
     * @param array<non-empty-string, list<Constraint>> $allowedRelationships
     * @param array<non-empty-string, list<Constraint>> $requiredRelationships
     *
     * @return list<Constraint>
     */
    protected function getRelationshipConstraints(array $allowedRelationships, array $requiredRelationships): array
    {
        $relationshipConstraints = [
            // validate request relationships are allowed and valid
            $this->getCollectionConstraintFactory()->noExtra(self::RELATIONSHIPS_CONTEXT, $allowedRelationships),
        ];

        // only create a validation for required relationships if there are any required
        // quick-fix for https://github.com/symfony/symfony/pull/53383
        if ([] !== $requiredRelationships) {
            // validate required relationships are present
            $relationshipConstraints[] = $this->getCollectionConstraintFactory()->noMissing(self::RELATIONSHIPS_CONTEXT, $requiredRelationships);
        }

        return $relationshipConstraints;
    }
}
