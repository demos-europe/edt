<?php

declare(strict_types=1);

namespace Tests\Querying\FluentQueries;

use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\FluentQueries\ConditionDefinition;
use EDT\Querying\FluentQueries\SliceDefinition;
use EDT\Querying\FluentQueries\SortDefinition;
use EDT\Querying\ObjectProviders\PrefilledObjectProvider;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\FluentQueries\FluentQuery;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use Tests\data\Model\Person;
use Tests\ModelBasedTest;

class FetchRequestBuilderFactoryTest extends ModelBasedTest
{
    /**
     * @var PhpConditionFactory
     */
    private $conditionFactory;
    /**
     * @var PrefilledObjectProvider
     */
    private $authorProvider;
    /**
     * @var PhpSortMethodFactory
     */
    private $sortMethodFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conditionFactory = new PhpConditionFactory();
        $propertyAccessor = new ReflectionPropertyAccessor();
        $this->authorProvider = new PrefilledObjectProvider(
            new ConditionEvaluator(new TableJoiner($propertyAccessor)),
            new Sorter(new TableJoiner($propertyAccessor)),
            $this->authors
        );
        $this->sortMethodFactory = new PhpSortMethodFactory();
    }

    /**
     * @return FluentQuery<FunctionInterface<bool>, SortMethodInterface, Person>
     */
    protected function createFetchRequest(): FluentQuery
    {
        return new FluentQuery(
            $this->authorProvider,
            new ConditionDefinition($this->conditionFactory, true),
            new SortDefinition($this->sortMethodFactory),
            new SliceDefinition()
        );
    }

    public function testRootAnd(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getSortDefinition()
            ->propertyAscending('birth', 'country');
        $fetch->getConditionDefinition()
            ->propertyHasValue('Bloemfontein', 'birth', 'locality')
            ->propertyHasValue('Oranje-Freistaat', 'birth', 'country');

        $actualAuthors = $fetch->getEntities();
        $expectedAuthors = [$this->authors['tolkien']];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }

    public function testSubOr(): void
    {
        $fetchRequest = $this->createFetchRequest();

        $fetchRequest->getSortDefinition()
            ->propertyAscending('birth', 'country');

        $orCondition = $fetchRequest->getConditionDefinition()
            ->propertyIsNotNull('birth')
            ->anyConditionApplies();

        $orCondition->allConditionsApply()
            ->anyConditionApplies()
                ->propertyHasValue('Germany', 'birth', 'country')
                ->propertyHasValue('Oranje-Freistaat', 'birth', 'country');
        $orCondition->anyConditionApplies()
            ->propertyHasValue('John Ronald Reuel Tolkien', 'name')
            ->propertyHasValue('Manfred', 'name');

        $actualAuthors = $fetchRequest->getEntities();
        $expectedAuthors = [$this->authors['phen'], $this->authors['tolkien']];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }

    public function testUnique(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getSortDefinition()
            ->propertyAscending('birth', 'country');
        $fetch->getConditionDefinition()
            ->propertyHasValue('Bloemfontein', 'birth', 'locality')
            ->propertyHasValue('Oranje-Freistaat', 'birth', 'country');

        $actualAuthor = $fetch->getUniqueEntity();
        $expectedAuthors = $this->authors['tolkien'];

        self::assertEquals($expectedAuthors, $actualAuthor);
    }

    public function testUniqueMissing(): void
    {
        $fetch = $this->createFetchRequest();
        $fetch->getConditionDefinition()
            ->propertyHasValue('xyz', 'birth', 'locality');

        $actualAuthor = $fetch->getUniqueEntity();

        self::assertNull($actualAuthor);
    }

    public function testUniqueException(): void
    {
        $this->expectException(FluentQueryException::class);

        $fetchRequest = $this->createFetchRequest();

        $fetchRequest->getSortDefinition()
            ->propertyAscending('birth', 'country');

        $orCondition = $fetchRequest->getConditionDefinition()
            ->propertyIsNotNull('birth')
            ->anyConditionApplies();

        $orCondition->allConditionsApply()
            ->anyConditionApplies()
            ->propertyHasValue('Germany', 'birth', 'country')
            ->propertyHasValue('Oranje-Freistaat', 'birth', 'country');
        $orCondition->anyConditionApplies()
            ->propertyHasValue('John Ronald Reuel Tolkien', 'name')
            ->propertyHasValue('Manfred', 'name');

        $fetchRequest->getUniqueEntity();

        self::fail('Expected exception');
    }
}
