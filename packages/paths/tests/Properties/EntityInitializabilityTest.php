<?php

declare(strict_types=1);

namespace Tests\Properties;

use EDT\JsonApi\Properties\EntityInitializability;
use EDT\Wrapping\Properties\AttributeConstructorParameter;
use EDT\Wrapping\Properties\EntityData;
use PHPUnit\Framework\TestCase;

class EntityInitializabilityTest extends TestCase
{

    public function test__construct()
    {
        $className = EntityData::class;
        $reflectionClass = new \ReflectionClass($className);

        $initializability = new EntityInitializability(
            $className,
            $reflectionClass->getConstructor()->getParameters(),
            [new AttributeConstructorParameter('type', 'type')],
            []
        );

        $expected = $initializability->getExpectedProperties();
        self::assertCount(1, $expected->getRequiredAttributes());
        self::assertCount(1, $expected->getAllowedAttributes());
        self::assertCount(0, $expected->getRequiredRelationships());
        self::assertCount(0, $expected->getAllowedRelationships());
    }
}
