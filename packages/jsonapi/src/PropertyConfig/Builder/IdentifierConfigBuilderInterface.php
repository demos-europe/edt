<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

/**
 * @template TEntity of object
 */
interface IdentifierConfigBuilderInterface extends PropertyConfigBuilderInterface
{
    /**
     * @param null|callable(TEntity): non-empty-string $customReadCallback
     *
     * @return $this
     */
    public function readable(callable $customReadCallback = null): self;
}
