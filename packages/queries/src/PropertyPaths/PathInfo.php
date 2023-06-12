<?php

declare(strict_types=1);

namespace EDT\Querying\PropertyPaths;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;

/**
 * @internal
 */
class PathInfo
{
    public function __construct(
        protected readonly PropertyPathAccessInterface $path,
        protected readonly bool $toManyAllowed
    ) {}

    /**
     * Copies the given instance into a new one if the `$toManyAllowed` property is
     * set differently. Otherwise, the given instance will be returned.
     */
    public static function maybeCopy(PathInfo $pathInfo, bool $toManyAllowed): self
    {
        if ($pathInfo->toManyAllowed === $toManyAllowed) {
            return $pathInfo;
        }
        return new PathInfo($pathInfo->path, $toManyAllowed);
    }

    public function getPath(): PropertyPathAccessInterface
    {
        return $this->path;
    }

    public function isToManyAllowed(): bool
    {
        return $this->toManyAllowed;
    }

    /**
     * @return list<PropertyPathAccessInterface>
     */
    public static function getPropertyPaths(PathsBasedInterface $pathsBased): array
    {
        return array_map(
            static fn (PathInfo $pathInfo): PropertyPathAccessInterface => $pathInfo->path,
            $pathsBased->getPropertyPaths()
        );
    }
}
