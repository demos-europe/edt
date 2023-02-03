<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\OutputTransformation\DynamicTransformer;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\JsonApi\Schema\ContentField;
use EDT\JsonApi\Schema\RelationshipObject;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\CreatableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use Exception;
use InvalidArgumentException;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use function array_key_exists;

/**
 * @template TCondition of FunctionInterface<bool>
 * @template TSorting of PathsBasedInterface
 *
 * @phpstan-import-type JsonApiRelationship from RelationshipObject
 * @phpstan-import-type JsonApiRelationships from RelationshipObject
 */
abstract class AbstractApiService
{
    /**
     * @param FilterParserInterface<mixed, TCondition> $filterParser
     * @param JsonApiSortingParser<TSorting> $sortingParser
     * @param PropertyValuesGenerator<TCondition, TSorting> $propertyValuesGenerator
     * @param TypeProviderInterface<TCondition, TSorting> $typeProvider
     */
    public function __construct(
        private readonly FilterParserInterface $filterParser,
        private readonly JsonApiSortingParser $sortingParser,
        private readonly PaginatorFactory $paginatorFactory,
        protected readonly PropertyValuesGenerator $propertyValuesGenerator,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly MessageFormatter $messageFormatter,
        protected readonly LoggerInterface $logger,
        protected readonly WrapperObjectFactory $wrapperFactory
    ) {}

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string $urlId
     */
    public function getFromRequest(string $urlTypeIdentifier, string $urlId, ParameterBag $urlParams): Item
    {
        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->getInstanceOrThrow();

        if (!$type->isExposedAsPrimaryResource()) {
            throw AccessException::typeNotDirectlyAccessible($type);
        }

        $entity = $this->getObject($type, $urlId);

        return new Item($entity, $this->createTransformer($type), $type->getIdentifier());
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
            ->getInstanceOrThrow();
        $this->assertExposureAsPrimaryResource($type);

        $filters = $this->getFilters($urlParams);
        $sortMethods = $this->getSorting($urlParams);

        $apiList = $this->getObjects($type, $filters, $sortMethods, $urlParams);

        $transformer = $this->createTransformer($type);
        $collection = new Collection($apiList->getList(), $transformer, $type->getIdentifier());
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
     * @param array{data: array{type: non-empty-string, id: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}} $requestBody
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
            ->getInstanceOrThrow();
        $this->assertExposureAsPrimaryResource($type);

        $updatedEntity = $this->updateObject($type, $urlId, $properties);
        if (null === $updatedEntity) {
            return null;
        }

        return new Item($updatedEntity, $this->createTransformer($type), $type->getIdentifier());
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
            ->getInstanceOrThrow();
        $this->assertExposureAsPrimaryResource($type);

        $this->deleteObject($type, $urlId);
    }

    /**
     * TODO: implement proper $requestBody validator to be called before this method
     *
     * @param non-empty-string $urlTypeIdentifier
     * @param array{data: array{type: non-empty-string, id?: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}} $requestBody
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

        // TODO: implement resource creation with client-provided ID
        if (array_key_exists(ContentField::ID, $data)) {
            throw new InvalidArgumentException('Creating objects from IDs provided in requests is currently not implemented.');
        }

        $attributes = $data[ContentField::ATTRIBUTES] ?? [];
        $relationships = $data[ContentField::RELATIONSHIPS] ?? [];
        $properties = $this->propertyValuesGenerator->generatePropertyValues($attributes, $relationships);

        $type = $this->typeProvider->requestType($urlTypeIdentifier)
            ->instanceOf(ResourceTypeInterface::class)
            ->instanceOf(CreatableTypeInterface::class)
            ->getInstanceOrThrow();
        $this->assertExposureAsPrimaryResource($type);

        $createdEntity = $this->createObject($type, $properties);
        if (null === $createdEntity) {
            return null;
        }

        return new Item($createdEntity, $this->createTransformer($type), $type->getIdentifier());
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

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, object> $type
     */
    private function assertExposureAsPrimaryResource(ResourceTypeInterface $type): void
    {
        if (!$type->isExposedAsPrimaryResource()) {
            throw AccessException::typeNotDirectlyAccessible($type);
        }
    }

    /**
     * @param TransferableTypeInterface<TCondition, TSorting, object> $transferableType
     */
    protected function createTransformer(TransferableTypeInterface $transferableType): TransformerAbstract
    {
        return new DynamicTransformer(
            $transferableType,
            $this->wrapperFactory,
            $this->messageFormatter,
            $this->logger
        );
    }
}
