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

/**
 * Instances can be used to create a response to a given JSON:API specification compliant HTTP `get` request, based on the configuration given in the constructor.
 */
class GetProcessor
{
    use ProcessorTrait;

    /**
     * Instead of creating instances of this class manually, you may want to use {@link Manager::createGetProcessor()}.
     *
     * @param array<non-empty-string, GetableTypeInterface<object>&PropertyReadableTypeInterface<object>> $getableTypes the types for which fetching of individual resources is supported by this instance
     * @param non-empty-string $resourceTypeAttribute the key to use when fetching the resource type name from the request's attributes
     * @param non-empty-string $resourceIdAttribute the key to use when fetching the resource ID from the request's attributes
     */
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ResponseFactory $responseFactory,
        protected readonly array $getableTypes,
        protected readonly string $resourceTypeAttribute,
        protected readonly string $resourceIdAttribute,
    ) {}

    /**
     * Create an HTTP response for the given request.
     *
     * @param Request $request The request fetch an individual resource for, must comply to the JSON:API specification.
     *
     * @return Response an HTTP response corresponding to the given `get` request
     *
     * @throws Exception
     */
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
