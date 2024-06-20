<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\ObjectProviders\MutableEntityProvider;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Reindexer;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TEntity of object
 *
 * @template-extends EntityProviderBasedRepository<FunctionInterface<bool>, SortMethodInterface, TEntity>
 */
class PhpEntityRepository extends EntityProviderBasedRepository
{
    /**
     * @param ConditionConverter<FunctionInterface<bool>> $conditionConverter
     * @param SortMethodConverter<SortMethodInterface> $sortMethodConverter
     * @param ConditionFactoryInterface<FunctionInterface<bool>> $conditionFactory
     * @param MutableEntityProvider<TEntity> $mutableEntityProvider
     */
    public function __construct(
        ConditionConverter $conditionConverter,
        SortMethodConverter $sortMethodConverter,
        protected readonly Reindexer $reindexer,
        ConditionFactoryInterface $conditionFactory,
        protected readonly MutableEntityProvider $mutableEntityProvider
    ) {
        parent::__construct($conditionConverter, $sortMethodConverter, $conditionFactory, $mutableEntityProvider);
    }

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


    public function deleteEntityByIdentifier(string $entityIdentifier, array $conditions, array $identifierPropertyPath): void
    {
        $conditions = $this->conditionConverter->convertConditions($conditions);

        $conditions[] = $this->conditionFactory->propertyHasValue($entityIdentifier, $identifierPropertyPath);
        $this->mutableEntityProvider->removeEntities($conditions, 1);
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
