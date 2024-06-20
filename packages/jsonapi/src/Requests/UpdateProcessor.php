<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\EmptyResponseTrait;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\PropertyBehavior\EntityVerificationTrait;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Instances can be used to create a response to a given request, based on the configuration given in the constructor.
 */
class UpdateProcessor
{
    use EmptyResponseTrait;
    use ProcessorTrait;
    use EntityVerificationTrait;

    /**
     * Instead of creating instances of this class manually, you may want to use {@link Manager::createUpdateProcessor()}.
     *
     * @param array<non-empty-string, UpdatableTypeInterface<object>&PropertyReadableTypeInterface<object>> $updatableTypes the types for which update requests should be accepted
     * @param non-empty-string $resourceTypeAttribute the key to use when fetching the resource type name from the request's attributes
     * @param non-empty-string $resourceIdAttribute the key to use when fetching the resource ID from the request's attributes
     * @param int<1, 8192> $maxBodyNestingDepth see {@link RequestWithBody::getRequestBody}
     */
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ResponseFactory $responseFactory,
        protected readonly RequestConstraintFactory $requestConstraintFactory,
        protected readonly array $updatableTypes,
        protected readonly string $resourceTypeAttribute,
        protected readonly string $resourceIdAttribute,
        protected int $maxBodyNestingDepth
    ) {}

    /**
     * Creates an HTTP response to the given request.
     *
     * @param Request $request The request to create a response for, must comply with the JSON:API specification.
     *
     * @return Response an HTTP response corresponding to the given update request, will be an empty response (no body) if the resource was adjusted exactly as requested
     *
     * @throws Exception
     */
    public function createResponse(Request $request): Response
    {
        [$typeName, $type] = $this->getType($request, $this->updatableTypes, $this->resourceTypeAttribute);
        $resourceId = $this->getUrlResourceId($request, $this->resourceIdAttribute);
        $resource = $this->updateResource($request, $type, $resourceId);

        return null === $resource
            ? $this->createEmptyResponse()
            : $this->responseFactory->createResourceResponse($request, $resource, $type, Response::HTTP_OK);
    }

    /**
     * @param UpdatableTypeInterface<object> $type
     * @param non-empty-string $resourceId
     *
     * @throws Exception
     */
    protected function updateResource(Request $request, UpdatableTypeInterface $type, string $resourceId): ?Item
    {
        $updateRequest = new UpdateRequest(
            $this->eventDispatcher,
            $request,
            $this->validator,
            $this->requestConstraintFactory,
            $this->maxBodyNestingDepth
        );
        return $updateRequest->updateResource($type, $resourceId);
    }
}
