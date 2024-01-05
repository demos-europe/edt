<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\ContentField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class RequestConstraintFactory
{
    use RequestConstraintTrait;

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return list<Constraint>
     */
    public function getBodyConstraints(
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollection $expectedProperties
    ): array {
        $requiredAttributes = $expectedProperties->getRequiredAttributes();
        $requiredRelationships = $expectedProperties->getRequiredRelationships();

        $attributeConstraints = $this->getAttributeConstraints($expectedProperties->getAllowedAttributes(), $requiredAttributes);
        $relationshipConstraints = $this->getRelationshipConstraints($expectedProperties->getAllowedRelationships(), $requiredRelationships);

        $isCreationRequestInsteadOfUpdate = null === $urlId;

        $outerDataConstraints = [
            // validate attributes and relationships
            new Assert\Collection(
                ['fields' => [
                    ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier),
                    ContentField::ID => $this->getIdConstraints($urlId),
                    ContentField::ATTRIBUTES => $attributeConstraints,
                    ContentField::RELATIONSHIPS => $relationshipConstraints,
                ]],
                null,
                null,
                false,
                true,
                'No other fields beside `type`, `id`, `attributes` or `relationships` must be present.'
            ),
            // validate `type` field
            new Assert\Collection(
                ['fields' => [ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier)]],
                null,
                null,
                true,
                false,
                null,
                'The field `type` must always be present.'
            ),
            // validate `id` field (only required if an ID was given in the request)
            new Assert\Collection(
                ['fields' => [ContentField::ID => $this->getIdConstraints($urlId)]],
                null,
                null,
                true,
                $isCreationRequestInsteadOfUpdate,
                null,
                $isCreationRequestInsteadOfUpdate
                    ? null
                    : 'Update requests must specify the `id` field.'
            ),
        ];

        if ([] !== $requiredAttributes) {
            $outerDataConstraints[] = new Assert\Collection(
                // no need to set the attribute constraints here, as they were already set above
                ['fields' => [ContentField::ATTRIBUTES => []]],
                null,
                null,
                true,
                false,
                null,
                'The field `attributes` must be present, as some attributes are required.'
            );
        }

        if ([] !== $requiredRelationships) {
            $outerDataConstraints[] = new Assert\Collection(
                // no need to set the relationship constraints here, as they were already set above
                ['fields' => [ContentField::RELATIONSHIPS => []]],
                null,
                null,
                true,
                false,
                null,
                'The field `relationships` must be present, as some relationships are required.'
            );
        }

        return [
            new Assert\Collection(
                ['fields' => [ContentField::DATA => $outerDataConstraints]],
                null,
                null,
                false,
                false,
                'No other fields must be present beside `data`.',
                'The field `data` must be present.'
            ),
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
        // validate request attributes are allowed and valid
        $allowedAttributesConstraint = new Assert\Collection(
            ['fields' => $allowedAttributes],
            null,
            null,
            false,
            true,
            'The access to at least one attribute was denied.'
        );

        $attributeConstraints = [
            $allowedAttributesConstraint,
        ];

        // only create a validation for required attributes if there are any required
        // quick-fix for https://github.com/symfony/symfony/pull/53383
        if ([] !== $requiredAttributes) {
            // validate required attributes are present
            $requiredAttributesConstraint = new Assert\Collection(
                ['fields' => $requiredAttributes],
                null,
                null,
                true,
                false,
                null,
                'At least one required attribute is missing.'
            );
            $attributeConstraints[] = $requiredAttributesConstraint;
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
        // validate request relationships are allowed and valid
        $allowedRelationshipConstraint = new Assert\Collection(
            ['fields' => $allowedRelationships],
            null,
            null,
            false,
            true,
            'The access to at least one relationship was denied.'
        );

        $relationshipConstraints = [
            $allowedRelationshipConstraint,
        ];

        // only create a validation for required relationships if there are any required
        // quick-fix for https://github.com/symfony/symfony/pull/53383
        if ([] !== $requiredRelationships) {
            // validate required relationships are present
            $requiredRelationshipConstraint = new Assert\Collection(
                ['fields' => $requiredRelationships],
                null,
                null,
                true,
                false,
                null,
                'At least one required relationship is missing.'
            );
            $relationshipConstraints[] = $requiredRelationshipConstraint;
        }

        return $relationshipConstraints;
    }
}
