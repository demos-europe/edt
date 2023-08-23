<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use EDT\Querying\Contracts\PathsBasedInterface;
use InvalidArgumentException;

/**
 * When used, instances require a specific attribute to be present in the request, which will
 * be directly used as constructor argument.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 *
 * @template-implements ConstructorParameterInterface<TCondition, TSorting>
 */
class AttributeConstructorParameter implements ConstructorParameterInterface
{
    /**
     * @param non-empty-string $attributeName
     * @param non-empty-string $argumentName
     */
    public function __construct(
        protected readonly string $attributeName,
        protected readonly string $argumentName
    ) {}

    public function getArgument(?string $entityId, EntityDataInterface $entityData): mixed
    {
        $attributes = $entityData->getAttributes();

        return $attributes[$this->attributeName]
            ?? throw new InvalidArgumentException("No attribute '$this->attributeName' present.");
    }

    public function getArgumentName(): string
    {
        return $this->argumentName;
    }

    public function getRequiredAttributes(): array
    {
        return [$this->attributeName];
    }

    public function getOptionalAttributes(): array
    {
        return [];
    }

    public function getRequiredToOneRelationships(): array
    {
        return [];
    }

    public function getOptionalToOneRelationships(): array
    {
        return [];
    }

    public function getRequiredToManyRelationships(): array
    {
        return [];
    }

    public function getOptionalToManyRelationships(): array
    {
        return [];
    }
}
