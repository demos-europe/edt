<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * Allows to expose {@link TypeInterface} instances for direct accesses via the JSON:API.
 */
interface ExposablePrimaryResourceTypeInterface
{
    /**
     * Determines if this type can be used directly in JSON:API requests.
     *
     * This affects if instances of this type can be accessed (e.g. read, if readable) directly
     * without being reached through a reference.
     */
    public function isExposedAsPrimaryResource(): bool;
}
