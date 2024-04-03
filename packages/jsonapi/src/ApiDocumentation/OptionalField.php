<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

/**
 * When sending a request that create resources or changes the state of existing ones, some properties may be required
 * in the request body.
 *
 * This is usually set to {@link OptionalField::YES} for the context of update requests. However, when creating
 * resources, it can be used to require specific properties, that are needed for a complete/valid state.
 */
enum OptionalField
{
    case YES;
    case NO;

    public static function fromBoolean(bool $boolean): self
    {
        return $boolean ? self::YES : self::NO;
    }

    public function equals(OptionalField $optionalField): bool
    {
        return $this === $optionalField;
    }
}
