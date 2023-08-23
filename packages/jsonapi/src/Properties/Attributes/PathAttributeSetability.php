<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\Properties\AbstractAttributeSetability;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends AbstractAttributeSetability<TCondition, TEntity>
 */
class PathAttributeSetability extends AbstractAttributeSetability
{
    use AttributeTrait;

    /**
     * @param non-empty-string $propertyName
     * @param class-string<TEntity> $entityClass
     * @param list<TCondition> $entityConditions
     * @param non-empty-list<non-empty-string> $propertyPath
     */
    public function __construct(
        string $propertyName,
        protected readonly string $entityClass,
        array $entityConditions,
        protected readonly mixed $propertyPath,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        bool $optional
    ) {
        parent::__construct($propertyName, $entityConditions, $optional);
    }

    protected function updateAttributeValue(object $entity, mixed $attributeValue): bool
    {
        $attributeValue = $this->assertValidValue($attributeValue);

        $propertyPath = $this->propertyPath;
        $propertyName = array_pop($propertyPath);
        $target = [] === $propertyPath
            ? $entity
            : $this->propertyAccessor->getValueByPropertyPath($entity, ...$propertyPath);
        Assert::object($target);
        $this->propertyAccessor->setValue($target, $attributeValue, $propertyName);

        return false;
    }
}
