<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathAccessInterface;

class PathTransformer
{
    /**
     * A prefix can be given if the conditions are to be executed against something
     * else the path the conditions were originally built for. The prefix will be
     * prepended to all paths within the given conditions.
     *
     * @param array<int, FunctionInterface> $conditions
     *
     * @throws PathException
     */
    public function prefixConditionPaths(array $conditions, string ...$prefix): void
    {
        array_walk($conditions, [$this, 'prefixConditionPath'], $prefix);
    }

    /**
     * @param array<int, string> $prefix
     *
     * @throws PathException
     */
    public function prefixConditionPath(FunctionInterface $condition, int $key, array $prefix): void
    {
        $paths = $condition->getPropertyPaths();
        $paths = array_filter(
            Iterables::asArray($paths),
            static function (PropertyPathAccessInterface $path): bool {
                return null === $path->getContext();
            }
        );
        array_walk($paths, [$this, 'prefixPath'], $prefix);
    }

    /**
     * @param array<int, string> $prefix
     *
     * @throws PathException
     */
    public function prefixPath(PropertyPathAccessInterface $path, int $key, array $prefix): void
    {
        if (null !== $path->getContext()) {
            throw PathException::contextBoundPrefixing($path, $prefix);
        }
        $path->setPath(...$prefix, ...$path);
    }
}
