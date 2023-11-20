<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Event\AfterCreationEvent;
use EDT\JsonApi\Event\BeforeCreationEvent;
use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class CreationRequest
{
    public function __construct(
        protected readonly RequestTransformer $requestTransformer,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, object> $type
     *
     * @throws Exception
     */
    public function createResource(CreatableTypeInterface $type): ?Item
    {
        $typeName = $type->getTypeName();
        $expectedProperties = $type->getExpectedInitializationProperties();

        $requestBody = $this->requestTransformer->getCreationRequestBody($typeName, $expectedProperties);
        $urlParams = $this->requestTransformer->getUrlParameters();

        $beforeCreationEvent = new BeforeCreationEvent($type);
        $this->eventDispatcher->dispatch($beforeCreationEvent);

        $modifiedEntity = $type->createEntity($requestBody);
        $entity = $modifiedEntity->getEntity();

        $afterCreationEvent = new AfterCreationEvent($type, $entity);
        $this->eventDispatcher->dispatch($afterCreationEvent);

        $requestDeviations = array_merge(
            $modifiedEntity->getRequestDeviations(),
            $beforeCreationEvent->getRequestDeviations(),
            $afterCreationEvent->getRequestDeviations()
        );

        if ([] === $requestDeviations) {
            // if there were no request deviations, no response body is needed
            return null;
        }

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
