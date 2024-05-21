<?php

declare(strict_types=1);

namespace Tests\Querying\ObjectProviders;

use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\ConditionParsers\Drupal\DrupalConditionParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\ObjectProviders\PrefilledEntityProvider;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use Symfony\Component\Validator\Validation;
use Tests\data\Model\Person;
use Tests\ModelBasedTest;

class PrefilledObjectProviderTest extends ModelBasedTest
{
    /**
     * @var PrefilledEntityProvider<Person, int>
     */
    private PrefilledEntityProvider $authorProvider;

    public function testUnconditionedAll()
    {
        $actual = $this->authorProvider->getEntities([], [], null);
        self::assertEquals($this->authors, $actual);
    }

    public function testUndconditionedOffsetSlice()
    {
        $actual = $this->authorProvider->getEntities([], [], new OffsetPagination(1, PHP_INT_MAX));
        $expected = $this->authors;
        array_shift($expected);
        self::assertEquals($expected, $actual);
    }

    public function testUndconditionedLimitSlice()
    {
        $actual = $this->authorProvider->getEntities([], [], new OffsetPagination(0, 2));
        $expected = $this->authors;
        $expected = [
            array_shift($expected),
            array_shift($expected),
        ];
        self::assertEquals($expected, array_values($actual));
    }

    public function testUndconditionedOffsetAndLimitSlice()
    {
        $actual = $this->authorProvider->getEntities([], [], new OffsetPagination(1, 2));
        $expected = $this->authors;
        array_shift($expected);
        $expected = [
            array_shift($expected),
            array_shift($expected),
        ];
        self::assertEquals($expected, array_values($actual));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $propertyAccessor = new ReflectionPropertyAccessor();
        $validator = Validation::createValidator();
        $phpConditionFactory = new PhpConditionFactory();
        $drupalConditionFactory = new PredefinedDrupalConditionFactory($phpConditionFactory);
        $filterValidator = new DrupalFilterValidator($validator, $drupalConditionFactory);
        $filterTransformer = new DrupalFilterParser($phpConditionFactory, new DrupalConditionParser($drupalConditionFactory), $filterValidator);
        $phpSortMethodFactory = new PhpSortMethodFactory();
        $sortingTransformer = new JsonApiSortingParser($phpSortMethodFactory);
        $sortingValidator = new SortValidator($validator);
        $this->authorProvider = new PrefilledEntityProvider(
            new ConditionEvaluator(new TableJoiner($propertyAccessor)),
            new Sorter(new TableJoiner($propertyAccessor)),
            $this->authors
        );
    }
}
