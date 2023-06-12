<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeUpdatabilityInterface<TCondition, TEntity>
 */
class CallbackAttributeUpdatability implements AttributeUpdatabilityInterface
{
    use AttributeTrait;

    /**
     * @param list<TCondition> $entityConditions
     * @param callable(TEntity, simple_primitive|array<int|string, mixed>|null): void $updateCallback
     */
    public function __construct(
        private readonly array $entityConditions,
        private readonly mixed $updateCallback
    ) {}

    public function updateAttributeValue(object $entity, mixed $attributeValue): void
    {
        $attributeValue = $this->assertValidValue($attributeValue);
        ($this->updateCallback)($entity, $attributeValue);
    }

    public function getEntityConditions(): array
    {
        return $this->entityConditions;
    }
}
