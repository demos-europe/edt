<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

/**
 * Represents a Resource Linkage as specified by jsonapi.org v1.1.
 *
 * @see https://jsonapi.org/format/1.1/#document-resource-object-relationships
 * @see https://jsonapi.org/format/1.1/#document-resource-object-linkage
 */
interface ResourceLinkageInterface
{
    public function getCardinality(): Cardinality;
}
