<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\JsonApi\OutputHandling\ResponseFactory;
use EDT\JsonApi\Pagination\PagePaginationParser;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\RequestHandling\PaginatorFactory;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface ListProcessorConfigInterface
{
    /**
     * @return DrupalFilterParser<TCondition>
     */
    public function getFilterTransformer(): DrupalFilterParser;

    /**
     * @return JsonApiSortingParser<TSorting>
     */
    public function getSortingTransformer(): JsonApiSortingParser;

    public function getPaginatorFactory(): PaginatorFactory;

    /**
     * @param positive-int $defaultPaginationPageSize
     */
    public function getPagPaginatorTransformer(int $defaultPaginationPageSize): PagePaginationParser;

    public function getSchemaPathProcessor(): SchemaPathProcessor;

    public function getEventDispatcher(): EventDispatcherInterface;

    public function getSortingValidator(): SortValidator;

    public function getFilterValidator(): DrupalFilterValidator;

    public function getResponseFactory(PropertyReadableTypeProviderInterface $typeProvider): ResponseFactory;
}
