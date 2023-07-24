<?php

declare(strict_types=1);

namespace EDT\JsonApi\EventListener;

use EDT\JsonApi\Event\CreationEvent;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;

class CreationListener
{
    use PropertyUpdaterTrait;
    use SideEffectHandleTrait;

    /**
     * @param CreationEvent<object> $creationEvent
     */
    public function __invoke(CreationEvent $creationEvent): void
    {
        $requestBody = $creationEvent->getCreationRequestBody();
        $entityClass = $creationEvent->getEntityClass();
        $attributeSetabilities = $creationEvent->getAttributeSetabilities();
        $toOneRelationshipSetabilities = $creationEvent->getToOneRelationshipSetabilities();
        $toManyRelationshipSetabilities = $creationEvent->getToManyRelationshipSetabilities();
        $constructorArguments = $creationEvent->getConstructorArguments();

        $entity = new $entityClass(...$constructorArguments);

        $sideEffects = [
            $this->updateAttributes($entity, $attributeSetabilities, $requestBody->getAttributes()),
            $this->updateToOneRelationships($entity, $toOneRelationshipSetabilities, $requestBody->getToOneRelationships()),
            $this->updateToManyRelationships($entity, $toManyRelationshipSetabilities, $requestBody->getToManyRelationships()),
        ];

        $creationEvent->setEntity($entity);
        $creationEvent->setSideEffects($this->mergeSideEffects($sideEffects));
    }
}
