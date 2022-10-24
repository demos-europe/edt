<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

/**
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface SortedListableTypeInterface
{
    /**
     * Get the sort method to apply when a collection of this property is fetched directly
     * and no sort methods were specified.
     * .*
     * Inside the method your paths can access all properties defined in the {@link TypeInterface::getInternalProperties()}
     * Thus you have unrestricted access to all properties of that schema and no limitations by {@link SortableTypeInterface::getSortableProperties}
     * will be applied.
     *
     * Note however, that if {@link AliasableTypeInterface::getAliases() aliases} are configured,
     * that they will be applied. The reasoning is the same as is in
     * {@link TypeInterface::getAccessCondition()}, where it is already explained in detail.
     *
     * Return an empty array to not define any default sorting.
     *
     * @return list<TSorting>
     */
    public function getDefaultSortMethods(): array;
}
