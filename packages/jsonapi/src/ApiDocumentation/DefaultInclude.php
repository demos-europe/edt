<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

/**
 * If this relationship should be included in the response if no specific includes were defined in the request.
 */
enum DefaultInclude
{
    case YES;
    case NO;

    public static function fromBoolean(bool $boolean): self
    {
        return $boolean ? self::YES : self::NO;
    }

    public function equals(DefaultInclude $defaultInclude): bool
    {
        return $this === $defaultInclude;
    }
}
