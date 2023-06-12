<?php

declare(strict_types=1);

namespace Tests\Wrapping\Utilities;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use EDT\Wrapping\TypeProviders\LazyTypeProvider;
use EDT\Wrapping\TypeProviders\PrefilledTypeProvider;
use EDT\Wrapping\Utilities\PhpEntityVerifier;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Tests\data\Types\AuthorType;
use Tests\data\Types\BookType;
use Tests\ModelBasedTest;

class PropertyReaderTest extends ModelBasedTest
{
    private AuthorType $authorType;

    private PropertyAccessorInterface $propertyAccessor;

    private SchemaPathProcessor $schemaPathProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $conditionFactory = new PhpConditionFactory();
        $lazyTypeProvider = new LazyTypeProvider();
        $this->propertyAccessor = new ReflectionPropertyAccessor();
        $propertyPathProcessorFactory = new PropertyPathProcessorFactory();
        $schemaPathProcessor = new SchemaPathProcessor($propertyPathProcessorFactory, $lazyTypeProvider);
        $tableJoiner = new TableJoiner($this->propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $entityVerifier = new PhpEntityVerifier($schemaPathProcessor, $conditionEvaluator, $sorter);
        $typeResolver = new AttributeTypeResolver();
        $this->authorType = new AuthorType(
            $conditionFactory,
            $lazyTypeProvider,
            $this->propertyAccessor,
            $entityVerifier,
            $typeResolver
        );
        $bookType = new BookType($conditionFactory, $lazyTypeProvider, $this->propertyAccessor, $typeResolver, $entityVerifier);
        $typeProvider = new PrefilledTypeProvider([$this->authorType, $bookType]);
        $lazyTypeProvider->setAllTypes($typeProvider);
        $this->schemaPathProcessor = new SchemaPathProcessor(new PropertyPathProcessorFactory(), $typeProvider);
    }

    public function testInternalConditionAliasWithoutAccess(): void
    {
        $tableJoiner = new TableJoiner($this->propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $author = $this->authors['phen'];
        $entityVerifier = new PhpEntityVerifier($this->schemaPathProcessor, $conditionEvaluator, $sorter);

        $value = $entityVerifier->filterEntity($author, [], $this->authorType);

        self::assertNull($value);
    }

    public function testInternalConditionAliasWithAccess(): void
    {
        $tableJoiner = new TableJoiner($this->propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $author = $this->authors['tolkien'];

        $entityVerifier = new PhpEntityVerifier($this->schemaPathProcessor, $conditionEvaluator, $sorter);

        $value = $entityVerifier->filterEntity($author, [], $this->authorType);

        self::assertSame($author, $value);
    }
}

