<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

/**
 * @template TResult of object
 */
interface BuildableInterface
{
    /**
     * @return TResult
     */
    public function build(): object;
}
