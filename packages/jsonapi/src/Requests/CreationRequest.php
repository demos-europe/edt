<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\RequestHandling\SideEffectHandleTrait;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use Exception;
use League\Fractal\Resource\Item;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class CreationRequest
{
    use PropertyUpdaterTrait;

    public function __construct(
        protected readonly RequestTransformer $requestTransformer,
    ) {}

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, object> $type
     *
     * @throws Exception
     */
    public function createResource(CreatableTypeInterface $type): ?Item
    {
        $typeName = $type->getTypeName();
        $expectedProperties = $type->getExpectedInitializationProperties();

        $requestBody = $this->requestTransformer->getCreationRequestBody($typeName, $expectedProperties);
        $urlParams = $this->requestTransformer->getUrlParameters();

        $entity = $type->createEntity($requestBody);

        if (null === $entity) {
            // if there were no side effects, no response body is needed
            return null;
        }

        return new Item($entity, $type->getTransformer(), $type->getTypeName());
    }
}
