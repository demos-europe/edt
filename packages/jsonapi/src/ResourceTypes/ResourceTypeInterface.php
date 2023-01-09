<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;
use League\Fractal\TransformerAbstract;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TransferableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends IdentifiableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends FilterableTypeInterface<TCondition, TSorting, TEntity>
 * @template-extends SortableTypeInterface<TCondition, TSorting, TEntity>
 */
interface ResourceTypeInterface extends
    TransferableTypeInterface,
    FilterableTypeInterface,
    SortableTypeInterface,
    IdentifiableTypeInterface,
    ExposablePrimaryResourceTypeInterface,
    AliasableTypeInterface
{
    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string;

    /**
     * Provides the transformer to convert instances of
     * {@link ResourceTypeInterface::getEntityClass} into JSON.
     */
    public function getTransformer(): TransformerAbstract;

    /**
     * @return array{0: array<non-empty-string, AttributeReadability<TEntity>>, 1: array<non-empty-string, ToOneRelationshipReadability<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>, 2: array<non-empty-string, ToManyRelationshipReadability<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>}
     */
    public function getReadableResourceTypeProperties(): array;
}
