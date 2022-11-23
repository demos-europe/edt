<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

/**
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractUpdatability<TCondition>
 */
class AttributeUpdatability extends AbstractUpdatability
{
    /**
     * @var null|callable(TEntity, string|int|float|bool|array<int|string, mixed>|null): void
     */
    private $customWriteFunction;

    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param null|callable(TEntity, string|int|float|bool|array<int|string, mixed>|null): void $customWriteFunction
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
     * @return null|callable(TEntity, string|int|float|bool|array<int|string, mixed>|null): void
     */
    public function getCustomWriteFunction(): ?callable
    {
        return $this->customWriteFunction;
    }
}
