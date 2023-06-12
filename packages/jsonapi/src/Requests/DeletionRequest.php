<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\Wrapping\Contracts\Types\IdDeletableTypeInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use Exception;

class DeletionRequest
{
    public function __construct(
        protected readonly RequestTransformer $requestParser
    ) {}

    /**
     * @param IdDeletableTypeInterface&NamedTypeInterface $type
     * @param non-empty-string $resourceId
     *
     * @throws DeletionFailedException
     */
    public function deleteResource(IdDeletableTypeInterface $type, string $resourceId): void
    {
        $typeName = $type->getTypeName();
        try {
            $urlParams = $this->requestParser->getUrlParameters();
            $type->deleteEntityByIdentifier($resourceId);
        } catch (Exception $exception) {
            throw new DeletionFailedException($resourceId, $typeName, $exception);
        }
    }
}
