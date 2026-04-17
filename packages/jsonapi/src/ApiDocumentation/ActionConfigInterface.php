<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;

interface ActionConfigInterface
{
    /**
     * If an empty string is returned, no description will be configured.
     *
     * @param non-empty-string $typeName
     */
    public function getOperationDescription(string $typeName): string;

    /**
     * If an empty string is returned, no description will be configured.
     */
    public function getPathDescription(): string;

    /**
     * @param non-empty-string $typeName
     *
     * @return non-empty-string
     */
    public function getSelfLink(string $typeName): string;
}
