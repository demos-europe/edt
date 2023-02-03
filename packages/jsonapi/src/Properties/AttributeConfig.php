<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\AttributeUpdatability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractConfig<TCondition, TEntity, AttributeReadability<TEntity>, AttributeUpdatability<TCondition, TEntity>>
 */
class AttributeConfig extends AbstractConfig
{
    /**
     * @param ResourceTypeInterface<TCondition, PathsBasedInterface, TEntity> $type
     */
    public function __construct(
        protected ResourceTypeInterface $type
    ) {}

    /**
     * @param null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $customValueFunction
     *
     * @return $this
     *
     * @throws ResourcePropertyConfigException
     */
    public function enableReadability(
        bool $defaultField = false,
        callable $customValueFunction = null,
        bool $allowingInconsistencies = false
    ): self {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->readability = new AttributeReadability(
            $defaultField,
            $allowingInconsistencies,
            $customValueFunction
        );

        return $this;
    }

    /**
     * @param list<TCondition>                                 $entityConditions
     * @param list<TCondition>                                 $valueConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): void $customWrite
     *
     * @return $this
     */
    public function enableUpdatability(
        array $entityConditions = [],
        array $valueConditions = [],
        callable $customWrite = null
    ): AttributeConfig {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->updatability = new AttributeUpdatability(
            $entityConditions,
            $valueConditions,
            $customWrite
        );

        return $this;
    }

    /**
     * @return ResourceTypeInterface<TCondition, PathsBasedInterface, TEntity>
     */
    protected function getType(): ResourceTypeInterface
    {
        return $this->type;
    }
}
