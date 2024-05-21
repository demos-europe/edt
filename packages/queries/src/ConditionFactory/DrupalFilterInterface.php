<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\Contracts\PathAdjustableInterface;

interface DrupalFilterInterface extends PathAdjustableInterface
{
    /**
     * Returns the content of this group as a flat list in the Drupal filter format.
     *
     * @param non-empty-string $name
     *
     * @return array<non-empty-string, array<non-empty-string, array<non-empty-string, mixed>>>
     */
    public function toDrupalArray(string $name, string $memberOf = ''): array;

    /**
     * @param null|callable(array<non-empty-string, mixed> $content): array<non-empty-string, mixed> $contentRewriter
     */
    public function deepCopy(callable $contentRewriter = null): DrupalFilterInterface;
}
