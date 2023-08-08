<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use Exception;

class DeletionRequest
{
    public function __construct(
        protected readonly RequestTransformer $requestParser
    ) {}

    /**
     * @param non-empty-string $resourceId
     *
     * @throws Exception
     */
    public function deleteResource(DeletableTypeInterface $type, string $resourceId): void
    {
        $urlParams = $this->requestParser->getUrlParameters();
        $type->deleteEntity($resourceId);
    }
}
