<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Wrapping\PropertyBehavior\ConstructorParameterInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 */
interface PropertyConfigInterface
{
    /**
     * @return PropertySetabilityInterface<TEntity>|null
     */
    public function getPostInstantiability(): ?PropertySetabilityInterface;

    public function getInstantiability(): ?ConstructorParameterInterface;

    /**
     * Returns `null` if this property can not be used to filter resources.
     */
    public function getFilterLink(): ?PropertyLinkInterface;

    /**
     * Returns `null` if this property can not be used to sort resources.
     */
    public function getSortLink(): ?PropertyLinkInterface;
}
