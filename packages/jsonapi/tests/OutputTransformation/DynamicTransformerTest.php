<?php

declare(strict_types=1);

namespace Tests\OutputTransformation;

use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\OutputTransformation\PropertyDefinitionInterface;
use EDT\JsonApi\RequestHandling\MessageFormatter;
use League\Fractal\Manager;
use League\Fractal\ParamBag;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use PHPUnit\Framework\TestCase;
use stdClass;

class DynamicTransformerTest extends TestCase
{
    private Manager $fractal;

    public function testEmpty(): void
    {
        $attributes = [
            'id' => $this->createIdPropertyDefinition(),
        ];
        $transformer = new DynamicTransformer(
            'Foobar',
            $attributes,
            [],
            new MessageFormatter(),
            null
        );

        self::assertEmpty($transformer->getAvailableIncludes());
        self::assertEmpty($transformer->getDefaultIncludes());

        $item = new Item($this->getInputData(), $transformer, 'Foobar');

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

    private function createIdPropertyDefinition(): PropertyDefinitionInterface
    {
        return new class() implements PropertyDefinitionInterface {
            public function determineData($entity, ParamBag $params)
            {
                return $entity->id;
            }

            public function isToBeUsedAsDefaultField(): bool
            {
                return true;
            }
        };
    }

    protected function getInputData(): object
    {
        $relationship = new stdClass();
        $relationship->id = '987';

        $inputData = new stdClass();
        $inputData->id = 'abc';
        $inputData->a = 1;
        $inputData->b = 2;
        $inputData->c = 3;
        $inputData->foo = $relationship;

        return $inputData;
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
    }
}
