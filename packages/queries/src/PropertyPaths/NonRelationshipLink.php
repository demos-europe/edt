<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

/**
 * Link to an attribute or the resource identifier.
 */
class NonRelationshipLink implements PropertyLinkInterface
{
    /**
     * @param non-empty-list<non-empty-string> $path
     */
    public function __construct(
        protected readonly array $path
    ) {}

    public function getPath(): array
    {
        return $this->path;
    }

    public function getAvailableTargetProperties(): ?array
    {
        return null;
    }
}
