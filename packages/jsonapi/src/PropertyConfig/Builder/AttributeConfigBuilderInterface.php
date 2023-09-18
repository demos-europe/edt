<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends ReadablePropertyConfigBuilderInterface<TEntity, simple_primitive|array<int|string, mixed>|null>
 * @template-extends InstantiablePropertyConfigBuilderInterface<TCondition, TEntity, simple_primitive|array<int|string, mixed>|null>
 */
interface AttributeConfigBuilderInterface extends PropertyConfigBuilderInterface, ReadablePropertyConfigBuilderInterface, InstantiablePropertyConfigBuilderInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $updateCallback
     *
     * @return $this
     */
    public function updatable(array $entityConditions = [], callable $updateCallback = null): self;
}
