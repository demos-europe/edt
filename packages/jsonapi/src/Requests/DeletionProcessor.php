<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\EmptyResponseTrait;
use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeletionProcessor
{
    use EmptyResponseTrait;
    use ProcessorTrait;

    /**
     * @param array<non-empty-string, DeletableTypeInterface> $deletableTypes
     * @param non-empty-string $resourceTypeAttribute
     * @param non-empty-string $resourceIdAttribute
     */
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly array $deletableTypes,
        protected readonly string $resourceTypeAttribute,
        protected readonly string $resourceIdAttribute,
    ) {}

    public function createResponse(Request $request): Response
    {
        [$typeName, $type] = $this->getType($request, $this->deletableTypes, $this->resourceTypeAttribute);
        $resourceId = $this->getUrlResourceId($request, $this->resourceIdAttribute);
        $this->deleteResource($type, $resourceId);

        return $this->createEmptyResponse();
    }

    /**
     * @param non-empty-string $resourceId
     *
     * @throws Exception
     */
    protected function deleteResource(DeletableTypeInterface $type, string $resourceId): void
    {
        $deletionRequest = new DeletionRequest($this->eventDispatcher);
        $deletionRequest->deleteResource($type, $resourceId);
    }
}
