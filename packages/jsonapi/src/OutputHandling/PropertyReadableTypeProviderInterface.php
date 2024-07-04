<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputHandling;

use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;

interface PropertyReadableTypeProviderInterface
{
    /**
     * @param non-empty-string $typeName
     * @return PropertyReadableTypeInterface<object>
     */
    public function getType(string $typeName): PropertyReadableTypeInterface;

    /**
     * @param non-empty-string $typeName
     * @param PropertyReadableTypeInterface<object> $type
     */
    public function addType(string $typeName, PropertyReadableTypeInterface $type): void;

    /**
     * @param PropertyReadableTypeInterface<object>&NamedTypeInterface $type
     */
    public function addNamedType(PropertyReadableTypeInterface&NamedTypeInterface $type): void;
}
