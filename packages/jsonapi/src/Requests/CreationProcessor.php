<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\EmptyResponseTrait;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Instances can be used to create a JSON:API specification compliant HTTP response to a given HTTP request, based on the configuration given in the constructor.
 */
class CreationProcessor
{
    use EmptyResponseTrait;
    use ProcessorTrait;

    /**
     * Instead of creating instances if this class manually, you may want to use {@link Manager::createCreationProcessor()}.
     *
     * @param array<non-empty-string, CreatableTypeInterface<object>&PropertyReadableTypeInterface<object>> $creatableTypes the types for which resource creation is supported by this instance
     * @param non-empty-string $resourceTypeAttribute the key to use when fetching the resource type name from the request's attributes
     * @param non-empty-string $resourceIdAttribute the key to use when fetching the resource ID from the request's attributes
     * @param int<1,8192> $maxBodyNestingDepth see {@link RequestWithBody::getRequestBody()}
     */
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ResponseFactory $responseFactory,
        protected readonly ValidatorInterface $validator,
        protected readonly RequestConstraintFactory $requestConstraintFactory,
        protected readonly array $creatableTypes,
        protected readonly string $resourceTypeAttribute,
        protected readonly string $resourceIdAttribute,
        protected readonly int $maxBodyNestingDepth
    ) {}

    /**
     * Create an HTTP response for the given request.
     *
     * @param Request $request The request to create a resource for, must comply to the JSON:API specification.
     *
     * @return Response an HTTP response corresponding to the given creation request, will be an empty response (no body) if the resource was created exactly as requested
     *
     * @throws Exception
     */
    public function createResponse(Request $request): Response
    {
        [$typeName, $type] = $this->getType($request, $this->creatableTypes, $this->resourceTypeAttribute);
        $resource = $this->createResource($request, $type);

        return null === $resource
            ? $this->createEmptyResponse()
            : $this->responseFactory->createResourceResponse($request, $resource, $type, Response::HTTP_CREATED);
    }

    /**
     * @param CreatableTypeInterface<object> $type
     *
     * @throws Exception
     */
    protected function createResource(Request $request, CreatableTypeInterface $type): ?Item
    {
        $creationRequest = new CreationRequest(
            $this->eventDispatcher,
            $request,
            $this->validator,
            $this->requestConstraintFactory,
            $this->maxBodyNestingDepth
        );

        return $creationRequest->createResource($type);
    }
}
