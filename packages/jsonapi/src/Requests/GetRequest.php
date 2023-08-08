<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Exception;
use League\Fractal\Resource\Item;

/**
 * This request fetches a single resource by its `id` property value.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class GetRequest
{
    public function __construct(
        protected readonly RequestTransformer $requestParser,
    ) {}

    /**
     * @param GetableTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-string $resourceId the identifier of the resource to be retrieved
     *
     * @throws Exception
     */
    public function getResource(GetableTypeInterface $type, string $resourceId): Item
    {
        $urlParams = $this->requestParser->getUrlParameters();
        $entity = $type->getEntity($resourceId);

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
