<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\AttributeUpdatability;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 * @template TValue
 *
 * @template-extends AbstractConfig<TCondition, TEntity, AttributeReadability<TEntity>, AttributeUpdatability<TCondition, TEntity>>
 */
abstract class AttributeConfig extends AbstractConfig
{
    /**
     * @param ResourceTypeInterface<TCondition, PathsBasedInterface, TEntity> $type
     */
    public function __construct(
        protected ResourceTypeInterface $type
    ) {}

    /**
     * @param null|callable(TEntity): TValue $customValueFunction
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

        $this->readability = $this->createAttributeReadability(
            $defaultField,
            $allowingInconsistencies,
            $customValueFunction
        );

        return $this;
    }

    /**
     * @param null|callable(TEntity): TValue $customValueFunction
     *
     * @return AttributeReadability<TEntity>
     */
    abstract protected function createAttributeReadability(
        bool $defaultField,
        bool $allowingInconsistencies,
        ?callable $customValueFunction
    ): AttributeReadability;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param null|callable(TEntity, TValue): void $customWrite
     *
     * @return $this
     */
    public function enableUpdatability(
        array $entityConditions = [],
        array $valueConditions = [],
        callable $customWrite = null
    ): AttributeConfig {
        $this->assertNullOrImplements(TransferableTypeInterface::class, 'readable');

        $this->updatability = new JsonAttributeUpdatability(
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
