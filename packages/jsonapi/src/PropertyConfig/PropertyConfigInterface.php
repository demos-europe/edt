<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 */
interface PropertyConfigInterface
{
    /**
     * @return list<PropertySetBehaviorInterface<TEntity>>
     */
    public function getPostConstructorBehaviors(): array;

    /**
     * @return list<ConstructorBehaviorInterface>
     */
    public function getConstructorBehaviors(): array;

    /**
     * Returns `null` if this property can not be used to filter resources.
     */
    public function getFilterLink(): ?PropertyLinkInterface;

    /**
     * Returns `null` if this property can not be used to sort resources.
     */
    public function getSortLink(): ?PropertyLinkInterface;
}
