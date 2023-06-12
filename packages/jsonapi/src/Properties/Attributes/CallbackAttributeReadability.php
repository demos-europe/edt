<?php

declare(strict_types=1);

namespace EDT\JsonApi\Properties\Attributes;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;

/**
 * @template TEntity of object
 *
 * @template-implements AttributeReadabilityInterface<TEntity>
 */
class CallbackAttributeReadability implements AttributeReadabilityInterface
{
    use AttributeTrait;

    /**
     * @param callable(TEntity): (simple_primitive|array<int|string, mixed>|null) $readCallback
     */
    public function __construct(
        protected readonly bool $defaultField,
        protected readonly mixed $readCallback,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getValue(object $entity): mixed
    {
        $propertyValue = ($this->readCallback)($entity);

        return $this->assertValidValue($propertyValue);
    }

    public function isDefaultField(): bool
    {
        return $this->defaultField;
    }

    public function getPropertySchema(): array
    {
        return $this->typeResolver->resolveReturnTypeFromCallable($this->readCallback);
    }
}
