<?php

declare(strict_types=1);

namespace Tests\Wrapping\TypeProviders;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\TypeProviders\LazyTypeProvider;
use EDT\Wrapping\TypeProviders\PrefilledTypeProvider;
use EDT\Wrapping\Utilities\PhpEntityVerifier;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use PHPUnit\Framework\TestCase;
use Tests\data\Types\AuthorType;
use Tests\data\Types\BookType;

class PrefilledTypeProviderTest extends TestCase
{
    protected PrefilledTypeProvider $typeProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $conditionFactory = new PhpConditionFactory();
        $lazyTypeProvider = new LazyTypeProvider();
        $propertyAccessor = new ReflectionPropertyAccessor();
        $propertyPathProcessorFactory = new PropertyPathProcessorFactory();
        $schemaPathProcessor = new SchemaPathProcessor($propertyPathProcessorFactory, $lazyTypeProvider);
        $tableJoiner = new TableJoiner($propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $typeResolver = new AttributeTypeResolver();
        $entityVerifier = new PhpEntityVerifier($schemaPathProcessor, $conditionEvaluator, $sorter);
        $authorType = new AuthorType($conditionFactory, $lazyTypeProvider, $propertyAccessor, $entityVerifier, $typeResolver);
        $bookType = new BookType($conditionFactory, $lazyTypeProvider, $propertyAccessor, $typeResolver, $entityVerifier);
        $this->typeProvider = new PrefilledTypeProvider([
            $authorType,
            $bookType,
        ]);
        $lazyTypeProvider->setAllTypes($this->typeProvider);
    }

    public function testUnknownTypeIdentifier(): void
    {
        $this->expectException(AccessException::class);
        $this->expectExceptionMessage("Type instance with identifier 'foobar' matching the defined criteria was not found due to the following reasons: identifier 'foobar' not known");
        $this->typeProvider->requestType('foobar')->getInstanceOrThrow();
    }
}
