<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface PathsBasedInterface
{
    /**
     * Get all {@link PropertyPathInterface property paths} of the implementation of
     * this interface.
     *
     * @return iterable<PropertyPathAccessInterface>
     */
    public function getPropertyPaths(): iterable;
}
