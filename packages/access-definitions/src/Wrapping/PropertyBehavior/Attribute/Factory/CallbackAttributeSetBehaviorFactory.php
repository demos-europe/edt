<?php

declare(strict_types=1);

namespace EDT\Wrapping\PropertyBehavior\Attribute\Factory;

use EDT\JsonApi\ApiDocumentation\OptionalField;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\CallbackAttributeSetBehavior;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityFactoryInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements PropertyUpdatabilityFactoryInterface<TCondition>
 */
class CallbackAttributeSetBehaviorFactory implements PropertyUpdatabilityFactoryInterface
{
    /**
     * @param list<TCondition> $entityConditions
     * @param callable(TEntity, simple_primitive|array<int|string, mixed>|null): list<non-empty-string> $updateCallback
     */
    public function __construct(
        protected readonly array $entityConditions,
        protected readonly mixed $updateCallback,
        protected OptionalField $optional
    ) {}

    /**
     * @param non-empty-string $name
     * @param non-empty-list<non-empty-string> $propertyPath
     * @param class-string<TEntity> $entityClass
     *
     * @return PropertyUpdatabilityInterface<TCondition, TEntity>
     */
    public function __invoke(string $name, array $propertyPath, string $entityClass): PropertyUpdatabilityInterface
    {
        return new CallbackAttributeSetBehavior(
            $name,
            $this->entityConditions,
            $this->updateCallback,
            $this->optional
        );
    }
}
