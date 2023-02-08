<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Querying\Contracts\PropertyPathInterface;
use IteratorAggregate;

/**
 * @template-extends IteratorAggregate<int, non-empty-string>
 */
interface PropertyAutoPathInterface extends PropertyPathInterface, IteratorAggregate
{
    public function setParent(PropertyAutoPathInterface $parent): void;

    /**
     * @param non-empty-string $parentPropertyName
     */
    public function setParentPropertyName(string $parentPropertyName): void;

    /**
     * Returns the instances that are part of this path.
     *
     * @return non-empty-list<PropertyAutoPathInterface> All objects that are part of this path,
     *                                                   including the starting object without
     *                                                   corresponding property name.
     */
    public function getAsValues(): array;
}
