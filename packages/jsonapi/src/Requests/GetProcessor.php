<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetProcessor
{
    use ProcessorTrait;

    /**
     * @param array<non-empty-string, GetableTypeInterface<object>&PropertyReadableTypeInterface<object>> $getableTypes
     * @param non-empty-string $resourceTypeAttribute
     * @param non-empty-string $resourceIdAttribute
     */
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ResponseFactory $responseFactory,
        protected readonly array $getableTypes,
        protected readonly string $resourceTypeAttribute,
        protected readonly string $resourceIdAttribute,
    ) {}

    public function createResponse(Request $request): Response
    {
        [$typeName, $type] = $this->getType($request, $this->getableTypes, $this->resourceTypeAttribute);
        $resource = $this->getResource($type, $this->getUrlResourceId($request, $this->resourceIdAttribute));

        return $this->responseFactory->createResourceResponse($request, $resource, $type, Response::HTTP_OK);
    }

    /**
     * @param GetableTypeInterface<object> $type
     * @param non-empty-string $resourceId
     *
     * @throws Exception
     */
    protected function getResource(GetableTypeInterface $type, string $resourceId): Item
    {
        $getRequest = new GetRequest($this->eventDispatcher);
        return $getRequest->getResource($type, $resourceId);
    }
}
