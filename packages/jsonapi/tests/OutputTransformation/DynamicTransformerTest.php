<?php

declare(strict_types=1);

namespace Tests\OutputTransformation;

use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use EDT\Wrapping\TypeProviders\LazyTypeProvider;
use EDT\Wrapping\TypeProviders\PrefilledTypeProvider;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\PropertyReader;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use stdClass;
use Tests\data\ApiTypes\EmptyType;
use Tests\data\EmptyEntity;
use Tests\data\Types\AuthorType;
use Tests\data\Types\BirthType;
use Tests\data\Types\BookType;

class DynamicTransformerTest extends TestCase
{
    private Manager $fractal;

    private WrapperObjectFactory $wrapperFactory;

    private PhpConditionFactory $conditionFactory;

    public function testEmpty(): void
    {
        $transformer = new DynamicTransformer(
            new EmptyType($this->conditionFactory),
            $this->wrapperFactory,
            new MessageFormatter(),
            null
        );

        self::assertEmpty($transformer->getAvailableIncludes());
        self::assertEmpty($transformer->getDefaultIncludes());

        $item = new Item(new EmptyEntity(), $transformer, 'Foobar');

        $outputData = $this->fractal->createData($item, 'Foobar');
        self::assertEquals(
            [
                'data' => [
                    'type'       => 'Foobar',
                    'attributes' => new stdClass(),
                    'id'         => 'abc',
                ],
            ],
            $outputData->toArray()
        );
        self::assertTrue(true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fractal = new Manager();

        $jsonApiSerializer = new JsonApiSerializer();

        $this->fractal->setSerializer($jsonApiSerializer);
        $this->fractal->setRecursionLimit(20);
        $this->fractal->parseIncludes([]);
        $this->fractal->parseFieldsets([]);
        $this->fractal->parseExcludes([]);

        $this->messageFormatter = new MessageFormatter();
        $conditionFactory = new PhpConditionFactory();
        $this->conditionFactory = $conditionFactory;
        $lazyTypeProvider = new LazyTypeProvider();
        $this->authorType = new AuthorType($conditionFactory, $lazyTypeProvider);
        $typeProvider = new PrefilledTypeProvider([
            $this->authorType,
            new BookType($conditionFactory, $lazyTypeProvider),
            new BirthType($conditionFactory),
        ]);
        $lazyTypeProvider->setAllTypes($typeProvider);
        $propertyAccessor = new ReflectionPropertyAccessor();
        $tableJoiner = new TableJoiner($propertyAccessor);
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);
        $this->wrapperFactory = new WrapperObjectFactory(
            new PropertyReader(
                new SchemaPathProcessor(new PropertyPathProcessorFactory(), $typeProvider),
                $conditionEvaluator,
                $sorter
            ),
            $propertyAccessor,
            $conditionEvaluator
        );
    }
}
