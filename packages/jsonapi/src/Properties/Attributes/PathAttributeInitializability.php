<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Properties\AttributeInitializabilityInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-implements AttributeInitializabilityInterface<TCondition, TEntity>
 * @template-extends PathAttributeSetability<TCondition, TEntity>
 */
class PathAttributeInitializability extends PathAttributeSetability implements AttributeInitializabilityInterface
{
    use OptionalInitializabilityTrait;

    /**
     * @param class-string<TEntity> $entityClass
     * @param list<TCondition> $entityConditions
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    public function __construct(
        string $entityClass,
        array $entityConditions,
        mixed $propertyPath,
        PropertyAccessorInterface $propertyAccessor
    ) {
        parent::__construct($entityClass, $entityConditions, $propertyPath, $propertyAccessor);
    }
}
