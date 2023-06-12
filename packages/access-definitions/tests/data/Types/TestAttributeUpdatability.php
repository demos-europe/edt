<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Properties\AttributeUpdatabilityInterface;
use Webmozart\Assert\Assert;

class TestAttributeUpdatability implements AttributeUpdatabilityInterface
{
    public function __construct(
        protected readonly array $propertyPath,
        protected readonly PropertyAccessorInterface $propertyAccessor
    ) {}

    public function updateAttributeValue(object $entity, mixed $value): void
    {
        $propertyPath = $this->propertyPath;
        $propertyName = array_pop($propertyPath);
        $target = [] === $propertyPath
            ? $entity
            : $this->propertyAccessor->getValueByPropertyPath($entity, ...$propertyPath);
        Assert::object($target);
        $this->propertyAccessor->setValue($target, $value, $propertyName);
    }

    public function getEntityConditions(): array
    {
        return [];
    }
}
