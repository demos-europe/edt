<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

class RelationshipLink implements PropertyLinkInterface
{
    /**
     * @var callable(): array<non-empty-string, PropertyLinkInterface>
     */
    protected $availableTargetPropertiesCallable;

    /**
     * @param non-empty-list<non-empty-string> $path
     * @param callable(): array<non-empty-string, PropertyLinkInterface> $availableTargetPropertiesCallable using a callback to avoid accidental recursion circles
     */
    public function __construct(
        protected readonly array $path,
        callable $availableTargetPropertiesCallable
    ) {
        $this->availableTargetPropertiesCallable = $availableTargetPropertiesCallable;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * @return array<non-empty-string, PropertyLinkInterface>
     */
    public function getAvailableTargetProperties(): array
    {
        return ($this->availableTargetPropertiesCallable)();
    }
}
