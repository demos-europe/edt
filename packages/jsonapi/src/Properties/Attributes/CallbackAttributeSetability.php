<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AbstractAttributeSetability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractAttributeSetability<TCondition, TEntity>
 */
class CallbackAttributeSetability extends AbstractAttributeSetability
{
    use AttributeTrait;

    /**
     * @param non-empty-string $propertyName
     * @param list<TCondition> $entityConditions
     * @param callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $setterCallback
     */
    public function __construct(
        string $propertyName,
        array $entityConditions,
        protected readonly mixed $setterCallback,
        bool $optional
    ) {
        parent::__construct($propertyName, $entityConditions, $optional);
    }

    protected function updateAttributeValue(object $entity, mixed $attributeValue): bool
    {
        $attributeValue = $this->assertValidValue($attributeValue);
        return ($this->setterCallback)($entity, $attributeValue);
    }
}
