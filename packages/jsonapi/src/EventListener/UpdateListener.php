<?php

declare(strict_types=1);

namespace EDT\JsonApi\EventListener;

use EDT\JsonApi\Event\UpdateEvent;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\Requests\PropertyUpdaterTrait;

class UpdateListener
{
    use PropertyUpdaterTrait;
    use SideEffectHandleTrait;

    /**
     * @param UpdateEvent<object> $updateEvent
     */
    public function __invoke(UpdateEvent $updateEvent): void
    {
        $attributeSetabilities = $updateEvent->getAttributeSetabilities();
        $toOneRelationshipSetabilities = $updateEvent->getToOneRelationshipSetabilities();
        $toManyRelationshipSetabilities = $updateEvent->getToManyRelationshipSetabilities();
        $requestBody = $updateEvent->getUpdateRequestBody();
        $entity = $updateEvent->getEntity();

        $sideEffects = [
            $this->updateAttributes($entity, $attributeSetabilities, $requestBody->getAttributes()),
            $this->updateToOneRelationships($entity, $toOneRelationshipSetabilities, $requestBody->getToOneRelationships()),
            $this->updateToManyRelationships($entity, $toManyRelationshipSetabilities, $requestBody->getToManyRelationships()),
        ];

        $updateEvent->setSideEffects($this->mergeSideEffects($sideEffects));
    }
}
