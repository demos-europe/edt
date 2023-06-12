<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Id;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use Webmozart\Assert\Assert;

/**
 * @template TEntity of object
 *
 * @template-implements IdReadabilityInterface<TEntity>
 */
class PathIdReadability implements IdReadabilityInterface
{
    /**
     * @param non-empty-list<non-empty-string> $propertyPath
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly mixed $propertyPath,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getValue(object $entity): string
    {
        $propertyValue = $this->propertyAccessor->getValueByPropertyPath($entity, ...$this->propertyPath);
        Assert::stringNotEmpty($propertyValue);

        return $propertyValue;
    }

    public function getPropertySchema(): array
    {
        return $this->typeResolver->resolveTypeFromEntityClass($this->entityClass, $this->propertyPath);
    }
}
