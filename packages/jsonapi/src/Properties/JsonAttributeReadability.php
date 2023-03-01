<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties;

use EDT\Wrapping\Properties\AttributeReadability;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * @template TEntity of object
 * @template-extends AttributeReadability<TEntity>
 */
class JsonAttributeReadability extends AttributeReadability
{
    /**
     * @param null|callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $customReadCallback
     */
    public function __construct(
        bool $defaultField,
        bool $allowingInconsistencies,
        $customReadCallback
    ) {
        parent::__construct($defaultField, $allowingInconsistencies, $customReadCallback);
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
