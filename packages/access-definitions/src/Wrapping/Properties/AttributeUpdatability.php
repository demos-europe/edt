<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractUpdatability<TCondition>
 */
class AttributeUpdatability extends AbstractUpdatability
{
    /**
     * @var null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): void
     */
    private $customWriteFunction;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): void $customWriteFunction
     */
    public function __construct(
        array $entityConditions,
        array $valueConditions,
        ?callable $customWriteFunction
    ) {
        parent::__construct($entityConditions, $valueConditions);
        $this->customWriteFunction = $customWriteFunction;
    }

    /**
     * @return null|callable(TEntity, simple_primitive|array<int|string, mixed>|null): void
     */
    public function getCustomWriteFunction(): ?callable
    {
        return $this->customWriteFunction;
    }
}
