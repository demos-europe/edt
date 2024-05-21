<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceConfig\Builder;

use EDT\Wrapping\PropertyBehavior\ConstructorBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertySetBehaviorInterface;
use EDT\Wrapping\PropertyBehavior\PropertyUpdatabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-implements ResourceConfigBuilderInterface<TEntity>
 */
abstract class BaseSchemaBuilder implements ResourceConfigBuilderInterface
{
    /**
     * @var list<ConstructorBehaviorInterface>
     */
    protected array $generalConstructorBehavior = [];

    /**
     * @var list<PropertySetBehaviorInterface<TEntity>>
     */
    protected array $generalPostConstructorBehavior = [];

    /**
     * @var list<PropertyUpdatabilityInterface<TEntity>>
     */
    protected array $generalUpdateBehaviors = [];

    public function addUpdateBehavior(PropertyUpdatabilityInterface $updateBehavior): ResourceConfigBuilderInterface
    {
        $this->generalUpdateBehaviors[] = $updateBehavior;

        return $this;
    }

    public function addConstructorBehavior(ConstructorBehaviorInterface $behavior): ResourceConfigBuilderInterface
    {
        $this->generalConstructorBehavior[] = $behavior;

        return $this;
    }

    public function removeAllCreationBehaviors(): self
    {
        $this->generalConstructorBehavior = [];
        $this->generalPostConstructorBehavior = [];

        return $this;
    }

    public function addPostConstructorBehavior(PropertySetBehaviorInterface $behavior): ResourceConfigBuilderInterface
    {
        return $this->addCreationBehavior($behavior);
    }

    public function addCreationBehavior(PropertySetBehaviorInterface $behavior): ResourceConfigBuilderInterface
    {
        $this->generalPostConstructorBehavior[] = $behavior;

        return $this;
    }

    public function removeAllUpdateBehaviors(): ResourceConfigBuilderInterface
    {
        $this->generalUpdateBehaviors = [];

        return $this;
    }
}
