<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use Symfony\Component\Validator\Constraint;

/**
 * Instances carry the information which attribute and relationship fields are allowed at all and which ones are
 * required in a JSON:API request body (creation or update). Alongside, they provide {@link Constraint} instances
 * with them to allow validating the content of those fields, e.g. the type of attribute values or if the relationships
 * contain the proper {@link https://jsonapi.org/format/#document-resource-object-relationships relationship link}
 * format.
 *
 * @see https://symfony.com/doc/current/validation.html Symfony Validation
 */
interface ExpectedPropertyCollectionInterface
{
    public function isIdAllowed(): bool;

    public function isIdRequired(): bool;

    /**
     * @return list<Constraint>
     */
    public function getIdConstraints(): array;

    /**
     * @param int<0, 8192> $validationLevelDepth
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getRequiredAttributes(int $validationLevelDepth, bool $allowAnythingBelowDepth): array;

    /**
     * @param int<0, 8192> $validationLevelDepth
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getAllowedAttributes(int $validationLevelDepth, bool $allowAnythingBelowDepth): array;

    /**
     * The list of constraints is likely empty, as they are already present in
     * {@link getAllowedRelationships} for all relationships and not just the required ones.
     *
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getRequiredRelationships(): array;

    /**
     * @return array<non-empty-string, list<Constraint>>
     */
    public function getAllowedRelationships(): array;
}
