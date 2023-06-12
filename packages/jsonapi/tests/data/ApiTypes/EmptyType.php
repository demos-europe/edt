<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\PropertyBuilderFactory;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use Pagerfanta\Pagerfanta;
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

    public function isExposedAsPrimaryResource(): bool
    {
        return false;
    }

    public function getIdentifierFilterPath(): array
    {
        return ['id'];
    }

    public function getIdentifier(): string
    {
        return 'Foobar';
    }

    public function getAccessCondition(): PathsBasedInterface
    {
        return $this->conditionFactory->false();
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }

    protected function getProperties(): array
    {
        return [];
    }

    public function fetchPagePaginatedResources(
        array $conditions,
        array $sortMethods,
        PagePagination $pagination
    ): Pagerfanta {
        throw new \RuntimeException('not implemented');
    }

    public function fetchResources(array $conditions, array $sortMethods): array
    {
        throw new \Exception('not implemented');
    }

    protected function getPropertyBuilderFactory(): PropertyBuilderFactory
    {
        return $this->propertyBuilderFactory;
    }

    public function getIdentifierSortingPath(): array
    {
        // TODO: Implement getIdentifierSortingPath() method.
    }

    public function getIdentifierReadability(): IdReadabilityInterface
    {
        return new PathIdReadability($this->getEntityClass(), ['id'], $this->propertyAccessor, $this->typeResolver);
    }

    public function fetchPagePaginatedEntities(
        array $conditions,
        array $sortMethods,
        PagePagination $pagination
    ): Pagerfanta {
        // TODO: Implement fetchPagePaginatedEntities() method.
    }

    public function fetchEntities(array $conditions, array $sortMethods): array
    {
        // TODO: Implement fetchEntities() method.
    }

    public function fetchEntity(int|string $entityIdentifier, array $conditions): ?object
    {
        // TODO: Implement fetchEntity() method.
    }
}
