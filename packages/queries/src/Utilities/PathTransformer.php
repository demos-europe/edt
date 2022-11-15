<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PathInfo;

class PathTransformer
{
    /**
     * A prefix can be given if the conditions are to be executed against something
     * else the path the conditions were originally built for. The prefix will be
     * prepended to all paths within the given conditions.
     *
     * @param list<PathsBasedInterface> $pathsBasedList
     * @param list<non-empty-string> $prefix
     *
     * @throws PathException
     */
    public function prefixPathsList(array $pathsBasedList, array $prefix): void
    {
        array_walk($pathsBasedList, [$this, 'prefixPaths'], $prefix);
    }

    /**
     * @param list<non-empty-string> $prefix
     *
     * @throws PathException
     */
    public function prefixPaths(PathsBasedInterface $pathsBased, int $key, array $prefix): void
    {
        $paths = array_filter(
            PathInfo::getPropertyPaths($pathsBased),
            static fn (PropertyPathAccessInterface $path): bool => null === $path->getContext()
        );
        array_walk($paths, [$this, 'prefixPath'], $prefix);
    }

    /**
     * @param list<non-empty-string> $prefix
     *
     * @throws PathException
     */
    public function prefixPath(PropertyPathAccessInterface $path, int $key, array $prefix): void
    {
        if ([] === $prefix) {
            return;
        }
        if (null !== $path->getContext()) {
            throw PathException::contextBoundPrefixing($path, $prefix);
        }

        $newPath = array_merge($prefix, $path->getAsNames());
        $path->setPath($newPath);
    }
}
