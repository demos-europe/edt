<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\Pagination\PagePaginationParser;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\RequestHandling\PaginatorFactory;
use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Exception;
use League\Fractal\Resource\Collection;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class ListProcessor
{
    use ProcessorTrait;

    /**
     * @param DrupalFilterParser<TCondition> $filterTransformer
     * @param JsonApiSortingParser<TSorting> $sortingTransformer
     * @param array<non-empty-string, ListableTypeInterface<TCondition, TSorting, object>&PropertyReadableTypeInterface<TCondition, TSorting, object>> $listableTypes
     * @param non-empty-string $resourceTypeAttribute
     */
    public function __construct(
        protected readonly DrupalFilterParser $filterTransformer,
        protected readonly DrupalFilterValidator $filterValidator,
        protected readonly JsonApiSortingParser $sortingTransformer,
        protected readonly SortValidator $sortingValidator,
        protected readonly PagePaginationParser $paginationTransformer,
        protected readonly PaginatorFactory $paginatorFactory,
        protected readonly SchemaPathProcessor $schemaPathProcessor,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ResponseFactory $responseFactory,
        protected readonly array $listableTypes,
        protected readonly string $resourceTypeAttribute
    ) {}

    public function createResponse(Request $request): JsonResponse
    {
        [$typeName, $type] = $this->getType($request, $this->listableTypes, $this->resourceTypeAttribute);
        $resource = $this->listResources($type, $request);

        return $this->responseFactory->createResourceResponse($request, $resource, $type, Response::HTTP_OK);
    }

    /**
     * @param ListableTypeInterface<TCondition, TSorting, object> $type
     *
     * @throws Exception
     */
    protected function listResources(ListableTypeInterface $type, Request $request): Collection
    {
        $listRequest = new ListRequest(
            $this->filterTransformer,
            $this->sortingTransformer,
            $this->paginatorFactory,
            $this->paginationTransformer,
            $request,
            $this->schemaPathProcessor,
            $this->eventDispatcher,
            $this->sortingValidator
        );

        return $listRequest->listResources($type);
    }
}
