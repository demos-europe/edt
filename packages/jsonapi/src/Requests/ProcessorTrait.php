<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

trait ProcessorTrait
{
    /**
     * @template T of object
     *
     * @param array<non-empty-string, T> $types
     * @param non-empty-string $resourceTypeAttribute
     *
     * @return array{non-empty-string, T} type name and corresponding type instance
     */
    protected function getType(Request $request, array $types, string $resourceTypeAttribute): array
    {
        $typeName = $request->attributes->get($resourceTypeAttribute);
        Assert::stringNotEmpty($typeName);
        $type = $types[$typeName] ?? throw new InvalidArgumentException("No type with the name `$typeName` was configured as usable for this kind of request.");

        return [$typeName, $type];
    }

    /**
     * @param non-empty-string $resourceIdAttribute
     *
     * @return non-empty-string
     */
    protected function getUrlResourceId(Request $request, string $resourceIdAttribute): string
    {
        $resourceId = $request->attributes->get($resourceIdAttribute);
        Assert::stringNotEmpty($resourceId);

        return $resourceId;
    }
}
