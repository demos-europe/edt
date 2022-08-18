<?php

declare(strict_types=1);

namespace Tests\Querying\FluentQueries;

use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\FluentQueryException;
use EDT\Querying\ObjectProviders\PrefilledObjectProvider;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\FluentQueries\FluentQuery;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
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
        $this->authorProvider = new PrefilledObjectProvider($propertyAccessor, $this->authors);
        $this->sortMethodFactory = new PhpSortMethodFactory();
    }

    /**
     * @return FluentQuery<Person>
     */
    protected function createFetchRequest(): FluentQuery
    {
        return FluentQuery::createWithDefaultDefinitions($this->conditionFactory, $this->sortMethodFactory, $this->authorProvider);
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
