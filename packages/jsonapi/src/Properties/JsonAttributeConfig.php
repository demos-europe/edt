<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeReadability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 * @template-extends AttributeConfig<TCondition, TEntity, simple_primitive|array<int|string, mixed>|null>
 */
class JsonAttributeConfig extends AttributeConfig
{
    /**
     * @param ResourceTypeInterface<TCondition, PathsBasedInterface, TEntity> $type
     */
    public function __construct(ResourceTypeInterface $type)
    {
        parent::__construct($type);
    }

    protected function createAttributeReadability(
        bool $defaultField,
        bool $allowingInconsistencies,
        ?callable $customReadCallback
    ): AttributeReadability {
        return new JsonAttributeReadability(
            $defaultField,
            $allowingInconsistencies,
            $customReadCallback
        );
    }
}
