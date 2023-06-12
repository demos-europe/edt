<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\Pagination\PagePaginationParser;
use EDT\JsonApi\RequestHandling\FilterParserInterface;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\RequestHandling\PaginatorFactory;
use EDT\JsonApi\RequestHandling\RequestTransformer;
use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Exception;
use InvalidArgumentException;
use League\Fractal\Resource\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class ListRequest
{
    /**
     * @param FilterParserInterface<mixed, TCondition> $filterParser
     * @param JsonApiSortingParser<TSorting> $sortingParser
     */
    public function __construct(
        protected readonly FilterParserInterface $filterParser,
        protected readonly JsonApiSortingParser $sortingParser,
        protected readonly PaginatorFactory $paginatorFactory,
        protected readonly PagePaginationParser $paginationParser,
        protected readonly RequestTransformer $requestParser,
        protected readonly SchemaPathProcessor $schemaPathProcessor,
    ) {}

    /**
     * @param ListableTypeInterface<TCondition, TSorting, object> $type
     *
     * @throws RequestException
     */
    public function listResources(ListableTypeInterface $type): Collection
    {
        $typeName = $type->getTypeName();
        try {
            $urlParams = $this->requestParser->getUrlParameters();

            $conditions = $this->getConditions($type, $urlParams);
            $sortMethods = $this->getSortMethods($type, $urlParams);
            $pagination = $this->paginationParser->getPagination($urlParams);

            $paginator = null;
            if (null === $pagination) {
                $entities = $type->getEntities($conditions, $sortMethods);
            } else {
                $paginator = $type->getEntitiesForPage($conditions, $sortMethods, $pagination);
                $entities = $paginator->getCurrentPageResults();
                $entities = array_values(Iterables::asArray($entities));
            }

            $collection = new Collection($entities, $type->getTransformer(), $typeName);
            $collection->setMeta([]);
            if (null !== $paginator) {
                $collection->setPaginator($this->paginatorFactory->createPaginatorAdapter($paginator));
            }

            return $collection;
        } catch (Exception $exception) {
            throw new ListFailedException("Failed to list `$typeName` resources.", 0, $exception);
        }
    }

    /**
     * @param TypeInterface<TCondition, TSorting, object> $type
     *
     * @return list<TCondition>
     *
     * @throws DrupalFilterException
     * @throws PathException
     */
    protected function getConditions(TypeInterface $type, ParameterBag $query): array
    {
        if (!$query->has(UrlParameter::FILTER)) {
            return [];
        }

        $filterParam = $query->get(UrlParameter::FILTER);
        $query->remove(UrlParameter::FILTER);
        $conditions = $this->filterParser->parseFilter($filterParam);

        if ([] === $conditions) {
            return [];
        }

        if (!$type instanceof FilteringTypeInterface) {
            throw AccessException::typeNotFilterable($type);
        }

        $this->schemaPathProcessor->mapFilterConditions($type, $conditions);

        return $conditions;
    }

    /**
     * @param TypeInterface<TCondition, TSorting, object> $type
     *
     * @return list<TSorting>
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    protected function getSortMethods(TypeInterface $type, ParameterBag $query): array
    {
        $sort = $query->get(UrlParameter::SORT);
        $query->remove(UrlParameter::SORT);

        Assert::stringNotEmpty($sort);
        $sortMethods = $this->sortingParser->createFromQueryParamValue($sort);

        if ([] === $sortMethods) {
            // If no sort methods were given then apply the default sort methods of the given type.
            return $type->getDefaultSortMethods();
        }

        if (!$type instanceof SortingTypeInterface) {
            throw AccessException::typeNotSortable($type);
        }

        $this->schemaPathProcessor->mapSorting($type, $sortMethods);

        return $sortMethods;
    }
}

