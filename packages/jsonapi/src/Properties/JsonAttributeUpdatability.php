<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\AttributeUpdatability;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AttributeUpdatability<TCondition, TEntity>
 */
class JsonAttributeUpdatability extends AttributeUpdatability
{
    /**
     * @param list<TCondition> $entityConditions
     * @param list<TCondition> $valueConditions
     * @param null|callable(TEntity, mixed): void $customWriteCallback
     */
    public function __construct(
        array $entityConditions,
        array $valueConditions,
        ?callable $customWriteCallback
    ) {
        parent::__construct($entityConditions, $valueConditions, $customWriteCallback);
    }

    /**
     * @phpstan-assert-if-true simple_primitive|array|null $attributeValue
     */
    public function isValidValue(mixed $attributeValue): bool
    {
        return null === $attributeValue
            || is_string($attributeValue)
            || is_int($attributeValue)
            || is_float($attributeValue)
            || is_bool($attributeValue)
            || is_array($attributeValue); // TODO: validate array content further?
    }
}
