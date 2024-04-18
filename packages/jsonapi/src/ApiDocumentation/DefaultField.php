<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

/**
 * By setting an attribute or relationship as "default", it will automatically be present in its resources returned in
 * the response, otherwise it will not be automatically present. If the request defined *any* specific properties to
 * return for *that* specific resource type, the default/non-default settings for all attributes and relationships
 * in *that* resource no effect.
 *
 * @see https://jsonapi.org/format/#fetching-sparse-fieldsets JSON:API sparse fieldsets
 */
enum DefaultField
{
    case YES;
    case NO;

    public static function fromBoolean(bool $boolean): self
    {
        return $boolean ? self::YES : self::NO;
    }

    public function equals(DefaultField $defaultField): bool
    {
        return $this === $defaultField;
    }
}
