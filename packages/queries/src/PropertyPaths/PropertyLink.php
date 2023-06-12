<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template TType of TypeInterface
 */
class PropertyLink
{
    /**
     * @param non-empty-list<non-empty-string> $path
     * @param TType $targetType
     */
    public function __construct(
        protected readonly array $path,
        protected readonly ?TypeInterface $targetType
    ) {}

    /**
     * @return TType|null
     */
    public function getTargetType(): ?TypeInterface
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
