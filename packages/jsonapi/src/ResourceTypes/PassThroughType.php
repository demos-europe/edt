<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\JsonApi\InputHandling\RepositoryInterface;
use EDT\JsonApi\OutputHandling\DynamicTransformer;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\PropertyPaths\PropertyLinkInterface;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use EDT\Wrapping\ResourceBehavior\ResourceReadability;
use EDT\Wrapping\ResourceBehavior\ResourceUpdatability;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;

/**
 * @template TEntity of object
 *
 * @template-extends AbstractResourceType<TEntity>
 * @template-implements UpdatableTypeInterface<TEntity>
 * @template-implements ListableTypeInterface<TEntity>
 */
class PassThroughType extends AbstractResourceType implements UpdatableTypeInterface, ListableTypeInterface
{
    protected readonly MessageFormatter $messageFormatter;

    /**
     * @param RepositoryInterface<TEntity> $repository
     * @param list<DrupalFilterInterface> $accessConditions
     * @param list<SortMethodInterface> $defaultSortMethods
     * @param ResourceInstantiability<TEntity> $instantiability
     * @param non-empty-list<non-empty-string> $identifierPropertyPath
     * @param class-string<TEntity> $entityClass
     * @param array<non-empty-string, PropertyLinkInterface> $filteringProperties
     * @param non-empty-string $typeName
     * @param ResourceReadability<TEntity> $readability
     * @param ResourceUpdatability<TEntity> $updatability
     * @param array<non-empty-string, PropertyLinkInterface> $sortingProperties
     */
    public function __construct(
        protected readonly string $entityClass,
        protected readonly RepositoryInterface $repository,
        protected readonly SchemaPathProcessor $schemaPathProcessor,
        protected readonly string $typeName,
        protected readonly array $accessConditions,
        protected readonly array $defaultSortMethods,
        protected readonly array $identifierPropertyPath,
        protected readonly array $filteringProperties,
        protected readonly array $sortingProperties,
        protected readonly ResourceInstantiability $instantiability,
        protected readonly ResourceReadability $readability,
        protected readonly ResourceUpdatability $updatability,
        protected readonly ?LoggerInterface $logger = null
    ){
        $this->messageFormatter = new MessageFormatter();
    }

    protected function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    protected function getAccessConditions(): array
    {
        return $this->accessConditions;
    }

    protected function getSchemaPathProcessor(): SchemaPathProcessor
    {
        return $this->schemaPathProcessor;
    }

    protected function getDefaultSortMethods(): array
    {
        return $this->defaultSortMethods;
    }

    protected function getInstantiability(): ResourceInstantiability
    {
        return $this->instantiability;
    }

    protected function getIdentifierPropertyPath(): array
    {
        return $this->identifierPropertyPath;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getFilteringProperties(): array
    {
        return $this->filteringProperties;
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function getReadability(): ResourceReadability
    {
        return $this->readability;
    }

    public function getUpdatability(): ResourceUpdatability
    {
        return $this->updatability;
    }

    public function getTransformer(): TransformerAbstract
    {
        return new DynamicTransformer(
            $this->getTypeName(),
            $this->getEntityClass(),
            $this->getReadability(),
            $this->messageFormatter,
            $this->logger
        );
    }

    public function getSortingProperties(): array
    {
        return $this->sortingProperties;
    }
}
