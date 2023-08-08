<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\EntityVerificationTrait;
use Exception;
use League\Fractal\Resource\Item;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class UpdateRequest
{
    use EntityVerificationTrait;
    use SideEffectHandleTrait;
    use PropertyUpdaterTrait;

    public function __construct(
        protected readonly RequestTransformer $requestTransformer,
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
        $requestBody = $this->requestTransformer->getUpdateRequestBody($typeName, $resourceId, $expectedProperties);
        $urlParams = $this->requestTransformer->getUrlParameters();

        $entity = $type->updateEntity($requestBody);

        if (null === $entity) {
            // if there were no side effects, no response body is needed
            return null;
        }

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
