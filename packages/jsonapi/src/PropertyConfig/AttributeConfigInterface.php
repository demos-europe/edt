<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends PropertyConfigInterface<TCondition, TEntity>
 */
interface AttributeConfigInterface extends PropertyConfigInterface
{
    /**
     * @return AttributeReadabilityInterface<TEntity>|null
     */
    public function getReadability(): ?AttributeReadabilityInterface;

    /**
     * @return PropertyUpdatabilityInterface<TCondition, TEntity>|null
     */
    public function getUpdatability(): ?PropertyUpdatabilityInterface;
}
