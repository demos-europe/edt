<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\ObjectProviders\MutableEntityProvider;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Pagination\PagePagination;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Reindexer;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use InvalidArgumentException;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TEntity of object
 *
 * @template-implements RepositoryInterface<TEntity>
 */
class PhpEntityRepository implements RepositoryInterface
{
    /**
     * @param ConditionConverter<FunctionInterface<bool>> $conditionConverter
     * @param SortMethodConverter<SortMethodInterface> $sortMethodConverter
     * @param ConditionFactoryInterface<FunctionInterface<bool>> $conditionFactory
     * @param MutableEntityProvider<TEntity> $entityProvider
     */
    public function __construct(
        protected readonly ConditionConverter $conditionConverter,
        protected readonly SortMethodConverter $sortMethodConverter,
        protected readonly Reindexer $reindexer,
        protected readonly ConditionFactoryInterface $conditionFactory,
        protected readonly MutableEntityProvider $entityProvider
    ) {}

    /**
     * @template TEnt of object
     *
     * @param list<TEnt> $entities
     *
     * @return PhpEntityRepository<TEnt>
     */
    public static function createDefault(
        ValidatorInterface $validator,
        PropertyAccessorInterface $propertyAccessor,
        array $entities
    ): self {
        $conditionFactory = new PhpConditionFactory();
        $sortMethodFactory = new PhpSortMethodFactory();
        $conditionConverter = ConditionConverter::createDefault($validator, $conditionFactory);
        $sortMethodConverter = SortMethodConverter::createDefault($validator, $sortMethodFactory);
        $tableJoiner = new TableJoiner($propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $reindexer = new Reindexer($conditionEvaluator, $sorter);
        $entityProvider = new MutableEntityProvider($conditionEvaluator, $sorter, $entities);

        return new self(
            $conditionConverter,
            $sortMethodConverter,
            $reindexer,
            $conditionFactory,
            $entityProvider
        );
    }

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

    public function deleteEntityByIdentifier(string $entityIdentifier, array $conditions, array $identifierPropertyPath): void
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);

        $conditions[] = $this->conditionFactory->propertyHasValue($entityIdentifier, $identifierPropertyPath);
        $this->entityProvider->removeEntities($conditions, 1);
    }

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);
        $sortMethods = $this->sortMethodConverter->convertSortMethods($sortMethods);

        return $this->reindexer->reindexEntities($entities, $conditions, $sortMethods);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);

        return $this->reindexer->isMatchingEntity($entity, $conditions);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);

        $this->reindexer->assertMatchingEntity($entity, $conditions);
    }
}
