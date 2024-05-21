<?php

declare(strict_types=1);

namespace EDT\JsonApi\PropertyConfig;

use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-extends PropertyConfigInterface<TEntity>
 */
interface AttributeConfigInterface extends PropertyConfigInterface
{
    /**
     * @return AttributeReadabilityInterface<TEntity>|null
     */
    public function getReadability(): ?AttributeReadabilityInterface;

    /**
     * @return list<PropertyUpdatabilityInterface<TEntity>>
     */
    public function getUpdateBehaviors(): array;
}
