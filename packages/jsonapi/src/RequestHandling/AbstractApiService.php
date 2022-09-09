<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\JsonApi\Schema\ContentField;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\UpdatableTypeInterface;
use Exception;
use InvalidArgumentException;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpFoundation\ParameterBag;
use function array_key_exists;

/**
 * @template F of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @psalm-type JsonApiRelationship = array{type: string, id: string}
 * @psalm-type JsonApiRelationships = array<string,array{data: array<int, JsonApiRelationship>|JsonApiRelationship|null}>
 */
abstract class AbstractApiService
{
    /**
     * @var PropertyValuesGenerator
     */
    protected $propertyValuesGenerator;

    /**
     * @var TypeProviderInterface
     */
    protected $typeProvider;

    /**
     * @var FilterParserInterface<mixed, F>
     */
    private $filterParser;

    /**
     * @var JsonApiSortingParser
     */
    private $sortingParser;

    /**
     * @var PaginatorFactory
     */
    private $paginatorFactory;

    /**
     * @param FilterParserInterface<mixed, F> $filterParser
     */
    public function __construct(
        FilterParserInterface $filterParser,
        JsonApiSortingParser $sortingParser,
        PaginatorFactory $paginatorFactory,
        PropertyValuesGenerator $propertyValuesGenerator,
        TypeProviderInterface $typeProvider
    ) {
        $this->propertyValuesGenerator = $propertyValuesGenerator;
        $this->typeProvider = $typeProvider;
        $this->filterParser = $filterParser;
        $this->sortingParser = $sortingParser;
        $this->paginatorFactory = $paginatorFactory;
    }

    public function getFromRequest(string $urlTypeName, string $urlId): Item
    {
        $type = $this->typeProvider->getAvailableType(
            $urlTypeName,
            ResourceTypeInterface::class
        );
        $entity = $this->getObject($type, $urlId);

        return new Item($entity, $type->getTransformer(), $type::getName());
    }

    /**
     * @throws DrupalFilterException
     */
    public function listFromRequest(string $typeName, ParameterBag $urlParams): Collection
    {
        $type = $this->typeProvider->getAvailableType(
            $typeName,
            ResourceTypeInterface::class
        );

        $filters = $this->getFilters($urlParams);
        $sortMethods = $this->getSorting($urlParams);

        $apiList = $this->getObjects($type, $filters, $sortMethods, $urlParams);

        $transformer = $type->getTransformer();
        $collection = new Collection($apiList->getList(), $transformer, $type::getName());
        $collection->setMeta($apiList->getMeta());
        $paginator = $apiList->getPaginator();
        if (null !== $paginator) {
            $collection->setPaginator($this->paginatorFactory->createPaginatorAdapter($paginator));
        }

        return $collection;
    }

    /**
     * @param array{data: array{type: string, id: string, attributes?: array<string,mixed>, relationships?: JsonApiRelationships}} $requestBody
     *
     * @throws Exception
     */
    // TODO: add proper format validation
    public function updateFromRequest(string $urlTypeName, string $urlId, array $requestBody): ?Item
    {
        if ('' === $urlTypeName || '' === $urlId) {
            throw new InvalidArgumentException('Invalid update request. Resource ID and type defined in URL must be non-empty strings.');
        }

        // "The PATCH request MUST include a single resource object as primary data."
        $data = $requestBody[ContentField::DATA] ?? [];
        // "The resource object MUST contain type and id members."
        $bodyId = $data[ContentField::ID] ?? '';
        $bodyTypeName = $data[ContentField::TYPE] ?? '';

        $bodyTypeName = $this->normalizeTypeName($bodyTypeName);
        if ($bodyId !== $urlId || $bodyTypeName !== $urlTypeName) {
            throw new InvalidArgumentException('Invalid update request. Resource ID and type defined in URL must match resource ID and type set in the request body.');
        }

        $attributes = $data[ContentField::ATTRIBUTES] ?? [];
        $relationships = $data[ContentField::RELATIONSHIPS] ?? [];
        $properties = $this->propertyValuesGenerator->generatePropertyValues($attributes, $relationships);

        /** @var ResourceTypeInterface&UpdatableTypeInterface $type */
        $type = $this->typeProvider->getAvailableType(
            $urlTypeName,
            ResourceTypeInterface::class,
            UpdatableTypeInterface::class
        );

        $updatedEntity = $this->updateObject($type, $urlId, $properties);
        if (null === $updatedEntity) {
            return null;
        }

        return new Item($updatedEntity, $type->getTransformer(), $type::getName());
    }

    public function deleteFromRequest(string $urlTypeName, string $urlId): void
    {
        $type = $this->typeProvider->getAvailableType(
            $urlTypeName,
            ResourceTypeInterface::class
        );

        $this->deleteObject($type, $urlId);
    }

    // TODO: add proper format validation

    /**
     * @param array{data: array{type: string, id: string, attributes?: array<string,mixed>, relationships?: JsonApiRelationships}} $requestBody
     *
     * @throws Exception
     */
    public function createFromRequest(string $urlTypeName, array $requestBody): ?Item
    {
        // "The request MUST include a single resource object as primary data."
        $data = $requestBody[ContentField::DATA] ?? [];
        // "The resource object MUST contain at least a type member."
        $bodyTypeName = $data[ContentField::TYPE] ?? null;
        if ($bodyTypeName !== $urlTypeName) {
            throw new InvalidArgumentException('Invalid creation request. Resource type defined in URL must match resource type send in the body.');
        }

        if (array_key_exists(ContentField::ID, $data)) {
            throw new InvalidArgumentException('Creating objects from IDs provided in requests is currently not implemented.');
        }

        $attributes = $data[ContentField::ATTRIBUTES] ?? [];
        $relationships = $data[ContentField::RELATIONSHIPS] ?? [];
        $properties = $this->propertyValuesGenerator->generatePropertyValues($attributes, $relationships);

        /** @var ResourceTypeInterface&CreatableTypeInterface $type */
        $type = $this->typeProvider->getAvailableType(
            $urlTypeName,
            ResourceTypeInterface::class,
            CreatableTypeInterface::class
        );

        $createdEntity = $this->createObject($type, $properties);
        if (null === $createdEntity) {
            return null;
        }

        return new Item($createdEntity, $type->getTransformer(), $type::getName());
    }

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<O> $type
     *
     * @return O
     */
    abstract protected function getObject(ResourceTypeInterface $type, string $id): object;

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<O> $type
     * @param array<string, mixed>     $properties
     *
     * @return O|null The created object if the creation had side effects on it (values set in
     *                the object beside the ones specified by the given $properties). `null`,
     *                if the resource was updated exactly as defined in
     *                the request (i.e. the FE has knows the current state of the resource).
     *
     * @throws Exception Thrown if the update was not successful
     */
    abstract protected function createObject(ResourceTypeInterface $type, array $properties): ?object;

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<O>        $type
     * @param array<int, F>                   $filters
     * @param array<int, SortMethodInterface> $sortMethods
     *
     * @return ApiListResultInterface<O>
     */
    abstract protected function getObjects(
        ResourceTypeInterface $type,
        array $filters,
        array $sortMethods,
        ParameterBag $urlParams
    ): ApiListResultInterface;

    abstract protected function normalizeTypeName(string $typeName): string;

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<O> $type
     * @param array<string, mixed>     $properties
     *
     * @return O|null The updated object if the update had side effects on it (changes to the
     *                object beside the ones specified by the given $properties). `null` otherwise.
     *
     * @throws AccessException
     * @throws Exception
     */
    abstract protected function updateObject(ResourceTypeInterface $type, string $id, array $properties): ?object;

    /**
     * @throws Exception
     */
    abstract protected function deleteObject(ResourceTypeInterface $type, string $id): void;

    /**
     * @return array<int, F>
     *
     * @throws DrupalFilterException
     */
    protected function getFilters(ParameterBag $query): array
    {
        if (!$query->has(UrlParameter::FILTER)) {
            return [];
        }

        $filterParam = $query->get(UrlParameter::FILTER);
        $conditions = $this->filterParser->parseFilter($filterParam);
        $query->remove(UrlParameter::FILTER);

        return $conditions;
    }

    /**
     * @return array<int,SortMethodInterface>
     */
    protected function getSorting(ParameterBag $query): array
    {
        $sort = $query->get(UrlParameter::SORT);
        $query->remove(UrlParameter::SORT);

        return $this->sortingParser->createFromQueryParamValue($sort);
    }
}
