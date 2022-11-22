<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\Updatability;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractConfig<TCondition, TEntity, AttributeReadability<TEntity>, Updatability<TCondition>>
 */
class AttributeConfig extends AbstractConfig
{
    protected ResourceTypeInterface $type;

    /**
     * @param ResourceTypeInterface<TCondition, PathsBasedInterface, TEntity> $type
     */
    public function __construct(ResourceTypeInterface $type)
    {
        $this->type = $type;
    }

    /**
     * @param null|callable(TEntity): (string|int|float|bool|array<int|string, mixed>|null) $customValueFunction
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

    protected function createUpdatability(array $entityConditions, array $valueConditions): Updatability
    {
        return new Updatability($entityConditions, $valueConditions);
    }

    protected function getType(): ?TypeInterface
    {
        return null;
    }
}
