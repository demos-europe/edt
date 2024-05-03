<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Webmozart\Assert\Assert;

class NameBasedTypeProvider implements PropertyReadableTypeProviderInterface
{
    /**
     * @var array<non-empty-string, PropertyReadableTypeInterface<PathsBasedInterface, PathsBasedInterface, object>>
     */
    protected array $types = [];

    public function addType(string $typeName, PropertyReadableTypeInterface $type): void
    {
        Assert::keyNotExists($this->types, $typeName);
        $this->types[$typeName] = $type;
    }

    public function getType(string $typeName): PropertyReadableTypeInterface
    {
        Assert::keyExists($this->types, $typeName);
        return $this->types[$typeName];
    }
}
