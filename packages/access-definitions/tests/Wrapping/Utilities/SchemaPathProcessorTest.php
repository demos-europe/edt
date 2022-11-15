<?php

declare(strict_types=1);

namespace Tests\Wrapping\Utilities;

use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Functions\Property;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\SortMethods\Ascending;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\TypeProviders\LazyTypeProvider;
use EDT\Wrapping\TypeProviders\PrefilledTypeProvider;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use InvalidArgumentException;
use Tests\data\Types\AuthorType;
use Tests\ModelBasedTest;

class SchemaPathProcessorTest extends ModelBasedTest
{
    private SchemaPathProcessor $schemaPathProcessor;

    private AuthorType $authorType;

    private PhpSortMethodFactory $sortMethodFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $conditionFactory = new PhpConditionFactory();
        $this->sortMethodFactory = new PhpSortMethodFactory();
        $lazyTypeProvider = new LazyTypeProvider();
        $this->authorType = new AuthorType($conditionFactory, $lazyTypeProvider);
        $typeProvider = new PrefilledTypeProvider([$this->authorType]);
        $lazyTypeProvider->setAllTypes($typeProvider);
        $this->schemaPathProcessor = new SchemaPathProcessor(new PropertyPathProcessorFactory(), $typeProvider);
    }

    public function testSortAccessException(): void
    {
        $this->expectException(AccessException::class);
        $this->expectExceptionMessage("Access with the path 'foo.bar' into the type class 'Tests\data\Types\AuthorType' was denied because of the path segment 'foo'.");
        $invalidSortMethod = $this->sortMethodFactory->propertyAscending(['foo', 'bar']);
        $this->schemaPathProcessor->mapSorting($this->authorType, [$invalidSortMethod]);
    }

    public function testMapSorting(): void
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK_RECURSIVE, ['name']);
        $sortMethod = new Ascending(new Property($propertyPath));
        $this->schemaPathProcessor->mapSorting($this->authorType, [$sortMethod]);
        $paths = $sortMethod->getPropertyPaths();
        self::assertCount(1, $paths);
        $path = array_pop($paths);
        self::assertSame(['name'], $path->getPath()->getAsNames());
    }
}
