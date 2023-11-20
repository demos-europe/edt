<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig\Builder;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 * @template TRelationship of object
 *
 * @template-extends ReadablePropertyConfigBuilderInterface<TEntity, list<TRelationship>>
 * @template-extends RelationshipConfigBuilderInterface<TCondition, TSorting, TEntity, TRelationship>
 */
interface ToManyRelationshipConfigBuilderInterface extends
    PropertyConfigBuilderInterface,
    ReadablePropertyConfigBuilderInterface,
    RelationshipConfigBuilderInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $relationshipConditions
     * @param null|callable(TEntity, list<TRelationship>): list<non-empty-string> $updateCallback
     *
     * @return $this
     */
    public function updatable(array $entityConditions = [], array $relationshipConditions = [], callable $updateCallback = null): self;

    /**
     * @param null|callable(TEntity, list<TRelationship>): list<non-empty-string> $postConstructorCallback
     * @param non-empty-string|null $customConstructorArgumentName the name of the constructor parameter, or `null` if it is the same as the name of this property
     * @param list<TCondition> $relationshipConditions
     *
     * @return $this
     */
    public function initializable(
        bool $optionalAfterConstructor = false,
        callable $postConstructorCallback = null,
        bool $constructorArgument = false,
        ?string $customConstructorArgumentName = null,
        array $relationshipConditions = []
    ): self;
}
