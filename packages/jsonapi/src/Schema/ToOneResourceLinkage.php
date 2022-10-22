<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

class ToOneResourceLinkage implements ResourceLinkageInterface
{
    private ?ResourceIdentifierObject $resourceIdentifierObject;

    private function __construct(?ResourceIdentifierObject $resourceIdentifierObject)
    {
        $this->resourceIdentifierObject = $resourceIdentifierObject;
    }

    /**
     * Accepts the "resource identifier object" specified by the JSON:API 1.0 in its array
     * representation or null. If it is not null a single {@link ResourceIdentifierObject} instance
     * will be created from the array format and is available using
     * {@link getResourceIdentifierObject}. If it is null this linkage instance it an empty
     * to-one resource linkage.
     *
     * > A “resource identifier object” MUST contain type and id members.
     *
     * @param array{type: non-empty-string, id: non-empty-string}|null $resourceIdentifierObject
     *
     * @return ToOneResourceLinkage
     *
     * @see https://jsonapi.org/format/#document-resource-identifier-objects
     */
    public static function createFromArray(?array $resourceIdentifierObject): self
    {
        return new self(null === $resourceIdentifierObject
            ? null
            : new ResourceIdentifierObject($resourceIdentifierObject)
        );
    }

    public function getCardinality(): Cardinality
    {
        return Cardinality::getToOne();
    }

    /**
     * The "resource identifier object" specified by the JSON:API this to-one linkage points
     * to or null if the linkage is empty.
     *
     * @see https://jsonapi.org/format/#document-resource-identifier-objects
     */
    public function getResourceIdentifierObject(): ?ResourceIdentifierObject
    {
        return $this->resourceIdentifierObject;
    }
}
