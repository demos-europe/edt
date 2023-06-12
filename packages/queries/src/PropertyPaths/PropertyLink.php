<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

/**
 * @template TType of object
 */
class PropertyLink
{
    /**
     * @param non-empty-list<non-empty-string> $path
     * @param TType|null $targetType
     */
    public function __construct(
        protected readonly array $path,
        protected readonly ?object $targetType
    ) {}

    /**
     * The relationship type of the last path segment or `null` if the path leads to an attribute.
     *
     * @return TType|null
     */
    public function getTargetType(): ?object
    {
        return $this->targetType;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getPath(): array
    {
        return $this->path;
    }
}
