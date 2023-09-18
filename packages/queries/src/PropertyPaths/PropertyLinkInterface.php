<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

interface PropertyLinkInterface
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPath(): array;

    /**
     * @return array<non-empty-string, PropertyLinkInterface>|null `null` if the target of this link is an attribute
     */
    public function getAvailableTargetProperties(): ?array;
}
