<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;

/**
 * @template TEntity of object
 * @template-implements AttributeReadabilityInterface<TEntity>
 */
class PathAttributeReadability implements AttributeReadabilityInterface
{
    use AttributeTrait;

    /**
     * @param class-string<TEntity> $entityClass
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly mixed $propertyPath,
        protected readonly bool $defaultField,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getValue(object $entity): mixed
    {
        $propertyValue = $this->propertyAccessor->getValueByPropertyPath($entity, ...$this->propertyPath);

        return $this->assertValidValue($propertyValue);
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    public function getPropertySchema(): array
    {
        return $this->typeResolver->resolveTypeFromEntityClass($this->entityClass, $this->propertyPath);
    }
}
