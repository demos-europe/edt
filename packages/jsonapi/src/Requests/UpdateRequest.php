<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Event\AfterUpdateEvent;
use EDT\JsonApi\Event\BeforeUpdateEvent;
use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\PropertyBehavior\EntityVerificationTrait;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class UpdateRequest
{
    use EntityVerificationTrait;
    public function __construct(
        protected readonly RequestTransformer $requestTransformer,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param UpdatableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-string $resourceId the identifier of the resource to be updated, must match the corresponding `id` field in the request body
     *
     * @throws Exception
     */
    public function updateResource(UpdatableTypeInterface $type, string $resourceId): ?Item
    {
        $typeName = $type->getTypeName();
        $expectedProperties = $type->getExpectedUpdateProperties();

        // get request data
        $requestBody = $this->requestTransformer->getUpdateRequestBody($typeName, $resourceId, $expectedProperties);
        $urlParams = $this->requestTransformer->getUrlParameters();

        $beforeUpdateEvent = new BeforeUpdateEvent($type, $resourceId);
        $this->eventDispatcher->dispatch($beforeUpdateEvent);

        $modifiedEntity = $type->updateEntity($requestBody->getId(), $requestBody);
        $entity = $modifiedEntity->getEntity();

        $afterUpdateEvent = new AfterUpdateEvent($type, $entity);
        $this->eventDispatcher->dispatch($afterUpdateEvent);

        $sideEffects = $modifiedEntity->hasSideEffects()
            || $beforeUpdateEvent->hasSideEffects()
            || $afterUpdateEvent->hasSideEffects();

        if (!$sideEffects) {
            // if there were no side effects, no response body is needed
            return null;
        }

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
