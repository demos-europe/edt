<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\JsonApi\Event\AfterListEvent;
use EDT\JsonApi\Event\BeforeListEvent;
use EDT\JsonApi\Pagination\PagePaginationParser;
use EDT\JsonApi\RequestHandling\FilterParserInterface;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\RequestHandling\PaginatorFactory;
use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Exception;
use InvalidArgumentException;
use League\Fractal\Resource\Collection;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ListRequest
{
    /**
     * @param FilterParserInterface<mixed, DrupalFilterInterface> $filterParser
     * @param JsonApiSortingParser<SortMethodInterface> $sortingParser
     */
    public function __construct(
        protected readonly FilterParserInterface $filterParser,
        protected readonly JsonApiSortingParser $sortingParser,
        protected readonly PaginatorFactory $paginatorFactory,
        protected readonly PagePaginationParser $paginationParser,
        protected readonly Request $request,
        protected readonly SchemaPathProcessor $schemaPathProcessor,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SortValidator $sortValidator
    ) {}

    /**
     * @param ListableTypeInterface<object> $type
     *
     * @throws Exception
     */
    public function listResources(ListableTypeInterface $type): Collection
    {
        $typeName = $type->getTypeName();
        $urlParams = $this->request->query;

        $conditions = $this->getConditions($urlParams);
        $sortMethods = $this->getSortMethods($urlParams);
        $pagination = $this->paginationParser->getPagination($urlParams);

        $beforeListEvent = new BeforeListEvent($type);
        $this->eventDispatcher->dispatch($beforeListEvent);

        $paginator = null;
        if (null === $pagination) {
            $entities = $type->getEntities($conditions, $sortMethods);
        } else {
            $paginator = $type->getEntitiesForPage($conditions, $sortMethods, $pagination);
            $entities = $paginator->getCurrentPageResults();
            $entities = array_values(Iterables::asArray($entities));
        }

        $afterListEvent = new AfterListEvent($type, $entities);
        $this->eventDispatcher->dispatch($afterListEvent);

        $collection = new Collection($entities, $type->getTransformer(), $typeName);
        $collection->setMeta([]);
        if (null !== $paginator) {
            $paginatorAdapter = $this->paginatorFactory->createPaginatorAdapter($paginator, $this->request);
            $collection->setPaginator($paginatorAdapter);
        }

        return $collection;
    }

    /**
     * @return list<DrupalFilterInterface>
     *
     * @throws DrupalFilterException
     * @throws PathException
     */
    protected function getConditions(ParameterBag $query): array
    {
        if (!$query->has(UrlParameter::FILTER)) {
            return [];
        }

        $filterParam = $query->get(UrlParameter::FILTER);
        $query->remove(UrlParameter::FILTER);
        $filterParam = $this->filterParser->validateFilter($filterParam);

        return $this->filterParser->parseFilter($filterParam);
    }

    /**
     * @return list<SortMethodInterface>
     *
     * @throws PathException
     * @throws InvalidArgumentException
     */
    protected function getSortMethods(ParameterBag $query): array
    {
        if (!$query->has(UrlParameter::SORT)) {
            return [];
        }

        $sort = $query->get(UrlParameter::SORT);
        $query->remove(UrlParameter::SORT);

        $sort = $this->sortValidator->validateFormat($sort);

        return $this->sortingParser->createFromQueryParamValue($sort);
    }
}

