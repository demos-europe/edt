<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use EDT\Querying\PropertyPaths\PathInfo;

interface PathsBasedInterface
{
    /**
     * Get all {@link PropertyPathInterface property paths} of the implementation of
     * this interface.
     *
     * @return list<PathInfo> may be empty
     */
    public function getPropertyPaths(): array;
}
