<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

interface DeletableTypeInterface
{
    /**
     * @param non-empty-string $entityIdentifier
     */
    public function deleteEntity(string $entityIdentifier): void;
}
