<?php

declare(strict_types=1);

namespace Tests\Querying\FluentQueries;

use EDT\ConditionFactory\ConditionFactory;
use EDT\JsonApi\InputHandling\ConditionConverter;
use EDT\JsonApi\InputHandling\PhpEntityRepository;
use EDT\JsonApi\InputHandling\SortMethodConverter;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\ConditionParsers\Drupal\DrupalConditionParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\OffsetEntityProviderInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\FluentQueries\ConditionDefinition;
use EDT\Querying\FluentQueries\SliceDefinition;
use EDT\Querying\FluentQueries\SortDefinition;
use EDT\Querying\ObjectProviders\MutableEntityProvider;
use EDT\Querying\ObjectProviders\PrefilledEntityProvider;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\FluentQueries\FluentQuery;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\SortMethodFactories\SortMethodFactory;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Reindexer;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use Symfony\Component\Validator\Validation;
use Tests\data\Model\Person;
use Tests\ModelBasedTest;

class FetchRequestBuilderFactoryTest extends ModelBasedTest
{
    private MutableEntityProvider $authorProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $propertyAccessor = new ReflectionPropertyAccessor();
        $tableJoiner = new TableJoiner($propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $this->authorProvider = new MutableEntityProvider(
            $conditionEvaluator,
            $sorter,
            $this->authors
        );
    }

    /**
     * @return FluentQuery<FunctionInterface<bool>, SortMethodInterface, Person>
     */
    protected function createFetchRequest(): FluentQuery
    {
        return new FluentQuery(
            new class ($this->authorProvider) implements OffsetEntityProviderInterface {
                public function __construct(protected readonly MutableEntityProvider $entityProvider){}

                public function getEntities(array $conditions, array $sortMethods, ?OffsetPagination $pagination): array
                {
                    $conditionConverter = ConditionConverter::createDefault(Validation::createValidator(), new PhpConditionFactory());
                    $sortMethodConverter = SortMethodConverter::createDefault(Validation::createValidator(), new PhpSortMethodFactory());
                    $conditions = $conditionConverter->convertConditions($conditions);
                    $sortMethods = $sortMethodConverter->convertSortMethods($sortMethods);

                    return $this->entityProvider->getEntities($conditions, $sortMethods, $pagination);
                }
            },
            new ConditionDefinition(new ConditionFactory(), true),
            new SortDefinition(new SortMethodFactory()),
            new SliceDefinition()
        );
    }

    public function testRootAnd(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getSortDefinition()
            ->propertyAscending(['birth', 'country']);
        $fetch->getConditionDefinition()
            ->propertyHasValue('Bloemfontein', ['birth', 'locality'])
            ->propertyHasValue('Oranje-Freistaat', ['birth', 'country']);

        $actualAuthors = $fetch->getEntities();
        $expectedAuthors = [$this->authors['tolkien']];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }

    public function testSubOr(): void
    {
        $fetchRequest = $this->createFetchRequest();

        $fetchRequest->getSortDefinition()
            ->propertyAscending(['birth', 'country']);

        $orCondition = $fetchRequest->getConditionDefinition()
            ->propertyIsNotNull(['birth'])
            ->anyConditionApplies();

        $orCondition->allConditionsApply()
            ->anyConditionApplies()
                ->propertyHasValue('Germany', ['birth', 'country'])
                ->propertyHasValue('Oranje-Freistaat', ['birth', 'country']);
        $orCondition->anyConditionApplies()
            ->propertyHasValue('John Ronald Reuel Tolkien', ['name'])
            ->propertyHasValue('Manfred', ['name']);

        $actualAuthors = $fetchRequest->getEntities();
        $expectedAuthors = [$this->authors['phen'], $this->authors['tolkien']];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }

    public function testUnique(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getSortDefinition()
            ->propertyAscending(['birth', 'country']);
        $fetch->getConditionDefinition()
            ->propertyHasValue('Bloemfontein', ['birth', 'locality'])
            ->propertyHasValue('Oranje-Freistaat', ['birth', 'country']);

        $actualAuthor = $fetch->getUniqueEntity();
        $expectedAuthors = $this->authors['tolkien'];

        self::assertEquals($expectedAuthors, $actualAuthor);
    }

    public function testUniqueMissing(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getConditionDefinition()
            ->propertyHasValue('xyz', ['birth', 'locality']);

        $actualAuthor = $fetch->getUniqueEntity();

        self::assertNull($actualAuthor);
    }

    public function testUniqueException(): void
    {
        $this->expectException(FluentQueryException::class);

        $fetchRequest = $this->createFetchRequest();

        $fetchRequest->getSortDefinition()
            ->propertyAscending(['birth', 'country']);

        $orCondition = $fetchRequest->getConditionDefinition()
            ->propertyIsNotNull(['birth'])
            ->anyConditionApplies();

        $orCondition->allConditionsApply()
            ->anyConditionApplies()
            ->propertyHasValue('Germany', ['birth', 'country'])
            ->propertyHasValue('Oranje-Freistaat', ['birth', 'country']);
        $orCondition->anyConditionApplies()
            ->propertyHasValue('John Ronald Reuel Tolkien', ['name'])
            ->propertyHasValue('Manfred', ['name']);

        $fetchRequest->getUniqueEntity();
    }
}
