<?php

declare(strict_types=1);

namespace Tests\Properties;

use EDT\Wrapping\EntityData;
use EDT\Wrapping\EntityDataInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeConstructorBehavior;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostConstructorBehaviorInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EntityConstructorBehaviorTest extends TestCase
{
    public function test__construct()
    {
        $className = EntityData::class;
        $reflectionClass = new ReflectionClass($className);

        $instantiability = new ResourceInstantiability(
            $className,
            ['f' => [new AttributeConstructorBehavior('type', 'type', null)]],
            [],
            [],
            [],
            [new class implements IdentifierPostConstructorBehaviorInterface {

                public function setIdentifier(object $entity, EntityDataInterface $entityData): array
                {
                    return [];
                }

                public function getRequiredAttributes(): array
                {
                    return [];
                }

                public function getOptionalAttributes(): array
                {
                    return [];
                }

                public function getRequiredToOneRelationships(): array
                {
                    return [];
                }

                public function getOptionalToOneRelationships(): array
                {
                    return [];
                }

                public function getRequiredToManyRelationships(): array
                {
                    return [];
                }

                public function getOptionalToManyRelationships(): array
                {
                    return [];
                }
            }]
        );

        $expected = $instantiability->getExpectedProperties();
        self::assertCount(1, $expected->getRequiredAttributes(1, false));
        self::assertCount(1, $expected->getAllowedAttributes(1, false));
        self::assertCount(0, $expected->getRequiredRelationships());
        self::assertCount(0, $expected->getAllowedRelationships());
    }
}
