<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Event\AfterDeletionEvent;
use EDT\JsonApi\Event\BeforeDeletionEvent;
use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeletionRequest
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param non-empty-string $resourceId
     *
     * @throws Exception
     */
    public function deleteResource(DeletableTypeInterface $type, string $resourceId): void
    {
        $beforeDeletionEvent = new BeforeDeletionEvent($type, $resourceId);
        $this->eventDispatcher->dispatch($beforeDeletionEvent);

        $type->deleteEntity($resourceId);

        $afterDeletionEvent = new AfterDeletionEvent($type, $resourceId);
        $this->eventDispatcher->dispatch($afterDeletionEvent);
    }
}
