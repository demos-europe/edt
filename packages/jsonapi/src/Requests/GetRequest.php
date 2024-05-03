<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Event\AfterGetEvent;
use EDT\JsonApi\Event\BeforeGetEvent;
use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * This request fetches a single resource by its `id` property value.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class GetRequest
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param GetableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-string $resourceId the identifier of the resource to be retrieved
     *
     * @throws Exception
     */
    public function getResource(GetableTypeInterface $type, string $resourceId): Item
    {
        $beforeGetEvent = new BeforeGetEvent($type);
        $this->eventDispatcher->dispatch($beforeGetEvent);

        $entity = $type->getEntity($resourceId);

        $afterGetEvent = new AfterGetEvent($type, $entity);
        $this->eventDispatcher->dispatch($afterGetEvent);

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
