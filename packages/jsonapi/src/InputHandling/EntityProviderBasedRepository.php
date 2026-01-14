<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\Querying\Contracts\OffsetEntityProviderInterface;
use EDT\ConditionFactory\ConditionFactoryInterface;
use InvalidArgumentException;
use EDT\Querying\Pagination\PagePagination;
use Pagerfanta\Pagerfanta;
use EDT\Querying\Pagination\OffsetPagination;
use Pagerfanta\Adapter\ArrayAdapter;

/**
 * @template TCondition the type of condition accepted by the logic accessing the actual data source (i.e. the given entity provider)
 * @template TSorting the type of sort method accepted by the logic accessing the actual data source (i.e. the given entity provider)
 * @template TEntity of object
 *
 * @template-implements RepositoryInterface<TEntity>
 */
abstract class EntityProviderBasedRepository implements RepositoryInterface
{
	/**
	 * @param ConditionConverter<TCondition> $conditionConverter
	 * @param SortMethodConverter<TSorting> $sortMethodConverter
	 * @param ConditionFactoryInterface<TCondition> $conditionFactory
	 * @param OffsetEntityProviderInterface<TCondition, TSorting, TEntity> $entityProvider
	 */
	public function __construct(
	    protected readonly ConditionConverter $conditionConverter,
	    protected readonly SortMethodConverter $sortMethodConverter,
	    protected readonly ConditionFactoryInterface $conditionFactory,
	    protected readonly OffsetEntityProviderInterface $entityProvider
	) {}

	public function getEntityByIdentifier(string $id, array $conditions, array $identifierPropertyPath): object
	{
	    $conditions = $this->conditionConverter->convertConditions($conditions);
	    $conditions[] = $this->conditionFactory->propertyHasValue($id, $identifierPropertyPath);
	    $entities = $this->entityProvider->getEntities($conditions, [], null);
	    
	    return match(count($entities)) {
	        0 => throw new InvalidArgumentException('No entity found for the given ID and conditions.'),
	        1 => array_pop($entities),
	        default => throw new InvalidArgumentException('Multiple entities found matching the given ID and conditions.')
	    };
	}

	public function getEntitiesByIdentifiers(array $identifiers, array $conditions, array $sortMethods, array $identifierPropertyPath): array
	{
	    $conditions = $this->conditionConverter->convertConditions($conditions);
	    $sortMethods = $this->sortMethodConverter->convertSortMethods($sortMethods);
	    $conditions[] = $this->conditionFactory->propertyHasAnyOfValues($identifiers, $identifierPropertyPath);
	    
	    return $this->entityProvider->getEntities($conditions, $sortMethods, null);
	}

	public function getEntities(array $conditions, array $sortMethods): array
	{
	    $conditions = $this->conditionConverter->convertConditions($conditions);
	    $sortMethods = $this->sortMethodConverter->convertSortMethods($sortMethods);
	    
	    return $this->entityProvider->getEntities($conditions, $sortMethods, null);
	}

	public function getEntitiesForPage(array $conditions, array $sortMethods, PagePagination $pagination): Pagerfanta
	{
	    $conditions = $this->conditionConverter->convertConditions($conditions);
	    $sortMethods = $this->sortMethodConverter->convertSortMethods($sortMethods);
	    
	    $pageSize = $pagination->getSize();
	    $pageNumber = $pagination->getNumber();
	    $paginator = new OffsetPagination(($pageNumber - 1) * $pageSize, $pageSize);
	    $entities = $this->entityProvider->getEntities($conditions, $sortMethods, $paginator);
	    
	    return new Pagerfanta(new ArrayAdapter($entities));
	}
}
