<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeInitializabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeInitializabilityInterface<TCondition, TEntity>
 * @template-extends CallbackAttributeSetability<TCondition, TEntity>
 */
class CallbackAttributeInitializability extends CallbackAttributeSetability implements AttributeInitializabilityInterface
{
    use OptionalInitializabilityTrait;

    /**
     * @param list<TCondition> $entityConditions
     * @param callable(TEntity, simple_primitive|array<int|string, mixed>|null): bool $setterCallback
     */
    public function __construct(array $entityConditions, mixed $setterCallback)
    {
        parent::__construct($entityConditions, $setterCallback);
    }
}
