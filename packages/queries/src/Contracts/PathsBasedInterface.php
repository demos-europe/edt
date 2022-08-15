<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface PathsBasedInterface
{
    /**
     * Get all {@link PropertyPathInterface property paths} of the implementation of
     * this interface.
     *
     * @return array<int, PropertyPathAccessInterface>
     */
    public function getPropertyPaths(): array;
}
