<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

interface PathAdjustableInterface
{
    /**
     * Takes a callable that takes the current path and returns the path to be set.
     *
     * @param callable(non-empty-list<non-empty-string>): non-empty-list<non-empty-string> $callable
     */
    public function adjustPath(callable $callable): void;
}
