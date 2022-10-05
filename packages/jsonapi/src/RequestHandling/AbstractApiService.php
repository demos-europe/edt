<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\JsonApi\Schema\ContentField;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
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
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\PathsBasedInterface
 *
 * @psalm-type JsonApiRelationship = array{type: non-empty-string, id: non-empty-string}
 * @psalm-type JsonApiRelationships = array<non-empty-string,array{data: list<JsonApiRelationship>|JsonApiRelationship|null}>
 */
abstract class AbstractApiService
{
    /**
     * @var PropertyValuesGenerator<TCondition, TSorting>
     */
    protected $propertyValuesGenerator;

    /**
     * @var TypeProviderInterface<TCondition, TSorting>
     */
    protected $typeProvider;

    /**
     * @var FilterParserInterface<mixed, TCondition>
     */
    private $filterParser;

    /**
     * @var JsonApiSortingParser<TSorting>
     */
    private $sortingParser;

    /**
     * @var PaginatorFactory
     */
    private $paginatorFactory;

    /**
     * @param FilterParserInterface<mixed, TCondition> $filterParser
     * @param JsonApiSortingParser<TSorting>         $sortingParser
     * @param PropertyValuesGenerator<TCondition, TSorting>   $propertyValuesGenerator
     * @param TypeProviderInterface<TCondition, TSorting>     $typeProvider
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

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string $urlId
     */
    public function getFromRequest(string $urlTypeIdentifier, string $urlId, ParameterBag $urlParams): Item
    {
        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->available(true)
            ->getTypeInstance();

        $entity = $this->getObject($type, $urlId);

        return new Item($entity, $type->getTransformer(), $type::getName());
    }

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @throws DrupalFilterException
     */
    public function listFromRequest(string $typeIdentifier, ParameterBag $urlParams): Collection
    {
        $type = $this->typeProvider->requestType($typeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->available(true)
            ->getTypeInstance();

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
     * @param non-empty-string                                                                                                                                   $urlTypeIdentifier
     * @param non-empty-string                                                                                                                                   $urlId
     * @param array{data: array{type: non-empty-string, id: non-empty-string, attributes?: array<non-empty-string,mixed>, relationships?: JsonApiRelationships}} $requestBody
     *
     * @throws Exception
     */
    public function updateFromRequest(string $urlTypeIdentifier, string $urlId, array $requestBody, ParameterBag $urlParams): ?Item
    {
        // "The PATCH request MUST include a single resource object as primary data."
        $data = $requestBody[ContentField::DATA];
        // "The resource object MUST contain type and id members."
        $bodyId = $data[ContentField::ID];
        $bodyTypeIdentifier = $data[ContentField::TYPE];

        $bodyTypeIdentifier = $this->normalizeTypeName($bodyTypeIdentifier);
        if ($bodyId !== $urlId || $bodyTypeIdentifier !== $urlTypeIdentifier) {
            throw new InvalidArgumentException('Invalid update request. Resource ID and type defined in URL must match resource ID and type set in the request body.');
        }

        $attributes = $data[ContentField::ATTRIBUTES] ?? [];
        $relationships = $data[ContentField::RELATIONSHIPS] ?? [];
        $properties = $this->propertyValuesGenerator->generatePropertyValues($attributes, $relationships);

        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->instanceOf(UpdatableTypeInterface::class)
            ->available(true)
            ->getTypeInstance();

        $updatedEntity = $this->updateObject($type, $urlId, $properties);
        if (null === $updatedEntity) {
            return null;
        }

        return new Item($updatedEntity, $type->getTransformer(), $type::getName());
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string $urlId
     *
     * @throws Exception
     */
    public function deleteFromRequest(string $urlTypeIdentifier, string $urlId): void
    {
        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->available(true)
            ->getTypeInstance();

        $this->deleteObject($type, $urlId);
    }

    /**
     * @param non-empty-string                                                                                                      $urlTypeIdentifier
     * @param array{data: array{type: string, id: string, attributes?: array<string, mixed>, relationships?: JsonApiRelationships}} $requestBody
     *
     * @throws Exception
     */
    public function createFromRequest(string $urlTypeIdentifier, array $requestBody, ParameterBag $urlParams): ?Item
    {
        // "The request MUST include a single resource object as primary data."
        $data = $requestBody[ContentField::DATA] ?? [];
        // "The resource object MUST contain at least a type member."
        $bodyTypeIdentifier = $data[ContentField::TYPE] ?? null;
        if ($bodyTypeIdentifier !== $urlTypeIdentifier) {
            throw new InvalidArgumentException('Invalid creation request. Resource type defined in URL must match resource type send in the body.');
        }

        if (array_key_exists(ContentField::ID, $data)) {
            throw new InvalidArgumentException('Creating objects from IDs provided in requests is currently not implemented.');
        }

        $attributes = $data[ContentField::ATTRIBUTES] ?? [];
        $relationships = $data[ContentField::RELATIONSHIPS] ?? [];
        $properties = $this->propertyValuesGenerator->generatePropertyValues($attributes, $relationships);

        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->instanceOf(CreatableTypeInterface::class)
            ->available(true)
            ->getTypeInstance();

        $createdEntity = $this->createObject($type, $properties);
        if (null === $createdEntity) {
            return null;
        }

        return new Item($createdEntity, $type->getTransformer(), $type::getName());
    }

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<TCondition, TSorting, O> $type
     * @param non-empty-string               $id
     *
     * @return O
     */
    abstract protected function getObject(ResourceTypeInterface $type, string $id): object;

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<TCondition, TSorting, O> $type
     * @param array<non-empty-string, mixed> $properties
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
     * @param ResourceTypeInterface<TCondition, TSorting, O> $type
     * @param list<TCondition>                        $filters
     * @param list<TSorting>                        $sortMethods
     *
     * @return ApiListResultInterface<O>
     */
    abstract protected function getObjects(
        ResourceTypeInterface $type,
        array $filters,
        array $sortMethods,
        ParameterBag $urlParams
    ): ApiListResultInterface;

    /**
     * @param non-empty-string $typeIdentifier
     *
     * @return non-empty-string
     */
    abstract protected function normalizeTypeName(string $typeIdentifier): string;

    /**
     * @template O of object
     *
     * @param ResourceTypeInterface<TCondition, TSorting, O> $type
     * @param non-empty-string               $id
     * @param array<non-empty-string, mixed> $properties
     *
     * @return O|null The updated object if the update had side effects on it (changes to the
     *                object beside the ones specified by the given $properties). `null` otherwise.
     *
     * @throws AccessException
     * @throws Exception
     */
    abstract protected function updateObject(ResourceTypeInterface $type, string $id, array $properties): ?object;

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, object> $type
     * @param non-empty-string                    $id
     *
     * @throws Exception
     */
    abstract protected function deleteObject(ResourceTypeInterface $type, string $id): void;

    /**
     * @return list<TCondition>
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
     * @return list<TSorting>
     */
    protected function getSorting(ParameterBag $query): array
    {
        $sort = $query->get(UrlParameter::SORT);
        $query->remove(UrlParameter::SORT);

        return $this->sortingParser->createFromQueryParamValue($sort);
    }
}
