<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeSetabilityInterface<TCondition, TEntity>
 */
class CallbackAttributeSetability implements AttributeSetabilityInterface
{
    use AttributeTrait;

    /**
     * @param list<TCondition>                                                        $entityConditions
     * @param callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $setterCallback
     */
    public function __construct(
        protected readonly array $entityConditions,
        protected readonly mixed $setterCallback
    ) {}

    public function updateAttributeValue(object $entity, mixed $attributeValue): bool
    {
        $attributeValue = $this->assertValidValue($attributeValue);
        return ($this->setterCallback)($entity, $attributeValue);
    }

    public function getEntityConditions(): array
    {
        return $this->entityConditions;
    }
}
