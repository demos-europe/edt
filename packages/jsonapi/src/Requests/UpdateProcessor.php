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

class UpdateProcessor
{
    use EmptyResponseTrait;
    use ProcessorTrait;
    use EntityVerificationTrait;

    /**
     * @param array<non-empty-string, UpdatableTypeInterface<object>&PropertyReadableTypeInterface<object>> $updatableTypes
     * @param non-empty-string $resourceTypeAttribute
     * @param non-empty-string $resourceIdAttribute
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
