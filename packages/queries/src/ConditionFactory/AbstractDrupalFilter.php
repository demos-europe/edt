<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PropertyPathInterface;

abstract class AbstractDrupalFilter
{
    /**
     * Returns the content of this group as a flat list in the Drupal filter format.
     *
     * @param non-empty-string $name
     *
     * @return array<non-empty-string, array<non-empty-string, array<non-empty-string, mixed>>>
     */
    abstract public function toDrupalArray(string $name, string $memberOf = ''): array;

    /**
     * @param null|callable(array<non-empty-string, mixed> $content): array<non-empty-string, mixed> $contentRewriter
     */
    abstract public function deepCopy(callable $contentRewriter = null): AbstractDrupalFilter;

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     *
     * @return non-empty-string
     */
    protected static function pathToString(string|array|PropertyPathInterface $path): string
    {
        if (is_string($path)) {
            return $path;
        }

        if (is_array($path)) {
            return implode('.', $path);
        }

        return $path->getAsNamesInDotNotation();
    }
}
