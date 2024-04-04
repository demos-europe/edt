<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\InputHandling\RepositoryInterface;
use EDT\JsonApi\PropertyConfig\Builder\IdentifierConfigBuilder;
use EDT\JsonApi\ResourceConfig\Builder\UnifiedResourceConfigBuilder;
use EDT\JsonApi\ResourceConfig\ResourceConfigInterface;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\Utilities\PropertyBuilderFactory;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use EDT\Wrapping\ResourceBehavior\ResourceReadability;
use EDT\Wrapping\ResourceBehavior\ResourceUpdatability;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use League\Fractal\TransformerAbstract;
use Tests\data\EmptyEntity;

class EmptyType extends AbstractResourceType
{
    public function __construct(
        protected readonly ConditionFactoryInterface $conditionFactory,
        protected readonly PropertyBuilderFactory $propertyBuilderFactory,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getEntityClass(): string
    {
        return EmptyEntity::class;
    }

    public function getTypeName(): string
    {
        return 'Foobar';
    }

    public function getAccessConditions(): array
    {
        return [$this->conditionFactory->false()];
    }

    protected function getSchemaPathProcessor(): SchemaPathProcessor
    {
        throw new \RuntimeException();
    }

    protected function getDefaultSortMethods(): array
    {
        throw new \RuntimeException();
    }

    protected function getRepository(): RepositoryInterface
    {
        throw new \RuntimeException();
    }

    protected function getIdentifierPropertyPath(): array
    {
        throw new \RuntimeException();
    }

    protected function getResourceConfig(): ResourceConfigInterface
    {
        $idProperty = new IdentifierConfigBuilder(
            $this->getEntityClass(),
            $this->propertyAccessor,
            $this->typeResolver
        );
        $idProperty->readable();
        $properties = [
            $idProperty,
        ];

        $resourceConfig = new UnifiedResourceConfigBuilder($this->getEntityClass(), $properties);

        return $resourceConfig->build();
    }

    public function getTransformer(): TransformerAbstract
    {
        throw new \RuntimeException();
    }

    protected function getInstantiability(): ResourceInstantiability
    {
        return $this->getResourceConfig()->getInstantiability();
    }

    public function getFilteringProperties(): array
    {
        return $this->getResourceConfig()->getFilteringProperties();
    }

    public function getReadability(): ResourceReadability
    {
        return $this->getResourceConfig()->getReadability();
    }

    public function getUpdatability(): ResourceUpdatability
    {
        return $this->getResourceConfig()->getUpdatability();
    }

    public function getSortingProperties(): array
    {
        return $this->getResourceConfig()->getSortingProperties();
    }
}
