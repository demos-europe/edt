<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

/**
 * Provides readability information and behavior for a relationship (i.e. a non-attribute) property.
 *
 * @template TRelationshipType of TransferableTypeInterface
 */
interface RelationshipReadabilityInterface extends PropertyReadabilityInterface
{
    /**
     * @return TRelationshipType
     */
    public function getRelationshipType(): TransferableTypeInterface;

    /**
     * @see https://jsonapi.org/format/#document-compound-documents
     * @see https://jsonapi.org/format/#fetching-includes
     */
    public function isDefaultInclude(): bool;
}
