<?php

declare(strict_types=1);

namespace Tests\OutputTransformation;

use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\OutputHandling\DynamicTransformer;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\Utilities\PropertyBuilderFactory;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Wrapping\TypeProviders\LazyTypeProvider;
use EDT\Wrapping\TypeProviders\PrefilledTypeProvider;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use stdClass;
use Tests\data\ApiTypes\EmptyType;
use Tests\data\EmptyEntity;
use Tests\data\Types\AuthorType;
use Tests\data\Types\BirthType;
use Tests\data\Types\BookType;
use Tests\ModelBasedTest;

class DynamicTransformerTest extends ModelBasedTest
{
    private Manager $fractal;

    private PhpConditionFactory $conditionFactory;

    private PropertyBuilderFactory $propertyBuilderFactory;
    private MessageFormatter $messageFormatter;
    private ReflectionPropertyAccessor $propertyAccessor;
    private AttributeTypeResolver $typeResolver;
    private AuthorType $authorType;

    public function testEmpty(): void
    {
        $type = new EmptyType($this->conditionFactory, $this->propertyBuilderFactory, $this->propertyAccessor, $this->typeResolver);

        $transformer = new DynamicTransformer(
            $type->getTypeName(),
            $type->getEntityClass(),
            $type->getReadability(),
            $this->messageFormatter,
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

        $this->propertyAccessor = new ReflectionPropertyAccessor();
        $this->messageFormatter = new MessageFormatter();
        $conditionFactory = new PhpConditionFactory();
        $this->conditionFactory = $conditionFactory;
        $lazyTypeProvider = new LazyTypeProvider();
        $this->typeResolver = new AttributeTypeResolver();
        $this->authorType = new AuthorType($conditionFactory, $lazyTypeProvider, $this->propertyAccessor, $this->typeResolver);
        $typeProvider = new PrefilledTypeProvider([
            $this->authorType,
            new BookType($conditionFactory, $lazyTypeProvider, $this->propertyAccessor, $this->typeResolver),
            new BirthType($conditionFactory),
        ]);
        $lazyTypeProvider->setAllTypes($typeProvider);
        $this->propertyBuilderFactory = new PropertyBuilderFactory(
            $this->propertyAccessor,
            $this->typeResolver
        );
    }
}
