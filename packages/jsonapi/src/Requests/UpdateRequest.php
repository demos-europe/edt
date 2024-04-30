<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Event\AfterUpdateEvent;
use EDT\JsonApi\Event\BeforeUpdateEvent;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollectionInterface;
use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\PropertyBehavior\EntityVerificationTrait;
use Exception;
use League\Fractal\Resource\Item;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class UpdateRequest
{
    use EntityVerificationTrait;

    public function __construct(
        protected readonly RequestTransformer $requestTransformer,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @param UpdatableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-string $resourceId the identifier of the resource to be updated, must match the corresponding `id` field in the request body
     *
     * @throws Exception
     */
    public function updateResource(UpdatableTypeInterface $type, string $resourceId): ?Item
    {
        $typeName = $type->getTypeName();
        $expectedProperties = $type->getExpectedUpdateProperties();

        // get request data
        $requestBody = $this->getUpdateRequestBody($typeName, $resourceId, $expectedProperties);

        $beforeUpdateEvent = new BeforeUpdateEvent($type, $requestBody);
        $this->eventDispatcher->dispatch($beforeUpdateEvent);

        $modifiedEntity = $type->updateEntity($requestBody->getId(), $requestBody);
        $entity = $modifiedEntity->getEntity();

        $afterUpdateEvent = new AfterUpdateEvent($type, $entity, $requestBody);
        $this->eventDispatcher->dispatch($afterUpdateEvent);

        $requestDeviations = array_merge(
            $modifiedEntity->getRequestDeviations(),
            $beforeUpdateEvent->getRequestDeviations(),
            $afterUpdateEvent->getRequestDeviations()
        );

        if ([] === $requestDeviations) {
            // if there were no request deviations, no response body is needed
            return null;
        }

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string $urlId
     */
    protected function getUpdateRequestBody(
        string $urlTypeIdentifier,
        string $urlId,
        ExpectedPropertyCollectionInterface $expectedProperties
    ): UpdateRequestBody {
        $body = $this->requestTransformer->getRequestData(
            $urlTypeIdentifier,
            $urlId,
            $expectedProperties
        );
        $relationships = $body[ContentField::RELATIONSHIPS] ?? [];
        [$toOneRelationships, $toManyRelationships] = $this->requestTransformer->splitRelationships($relationships);

        return new UpdateRequestBody(
            $urlId,
            $body[ContentField::TYPE],
            $body[ContentField::ATTRIBUTES] ?? [],
            $toOneRelationships,
            $toManyRelationships
        );
    }
}
