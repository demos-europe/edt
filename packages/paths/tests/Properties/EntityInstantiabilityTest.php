<?php

declare(strict_types=1);

namespace Tests\Properties;

use EDT\Wrapping\EntityData;
use EDT\Wrapping\EntityDataInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeConstructorParameter;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierPostInstantiabilityInterface;
use EDT\Wrapping\ResourceBehavior\ResourceInstantiability;
use PHPUnit\Framework\TestCase;

class EntityInstantiabilityTest extends TestCase
{
    public function test__construct()
    {
        $className = EntityData::class;
        $reflectionClass = new \ReflectionClass($className);

        $instantiability = new ResourceInstantiability(
            $className,
            [new AttributeConstructorParameter('type', 'type')],
            [],
            new class implements IdentifierPostInstantiabilityInterface {

                public function setIdentifier(object $entity, EntityDataInterface $entityData): bool
                {
                    // TODO: Implement setIdentifier() method.
                }

                public function getRequiredAttributes(): array
                {
                    // TODO: Implement getRequiredAttributes() method.
                }

                public function getOptionalAttributes(): array
                {
                    // TODO: Implement getOptionalAttributes() method.
                }

                public function getRequiredToOneRelationships(): array
                {
                    // TODO: Implement getRequiredToOneRelationships() method.
                }

                public function getOptionalToOneRelationships(): array
                {
                    // TODO: Implement getOptionalToOneRelationships() method.
                }

                public function getRequiredToManyRelationships(): array
                {
                    // TODO: Implement getRequiredToManyRelationships() method.
                }

                public function getOptionalToManyRelationships(): array
                {
                    // TODO: Implement getOptionalToManyRelationships() method.
                }
            }
        );

        $expected = $instantiability->getExpectedProperties();
        self::assertCount(1, $expected->getRequiredAttributes());
        self::assertCount(1, $expected->getAllowedAttributes());
        self::assertCount(0, $expected->getRequiredRelationships());
        self::assertCount(0, $expected->getAllowedRelationships());
    }
}
