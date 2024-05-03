<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\EmptyResponseTrait;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class CreationProcessor
{
    use EmptyResponseTrait;
    use ProcessorTrait;

    /**
     * @param array<non-empty-string, CreatableTypeInterface<TCondition, TSorting, object>&PropertyReadableTypeInterface<TCondition, TSorting, object>> $creatableTypes
     * @param non-empty-string $resourceTypeAttribute
     * @param non-empty-string $resourceIdAttribute
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

    public function createResponse(Request $request): Response
    {
        [$typeName, $type] = $this->getType($request, $this->creatableTypes, $this->resourceTypeAttribute);
        $resource = $this->createResource($request, $type);

        return null === $resource
            ? $this->createEmptyResponse()
            : $this->responseFactory->createResourceResponse($request, $resource, $type, Response::HTTP_CREATED);
    }

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, object> $type
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
