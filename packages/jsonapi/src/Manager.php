<?php

declare(strict_types=1);

namespace EDT\JsonApi;

use EDT\JsonApi\ApiDocumentation\OpenApiDocumentBuilder;
use EDT\JsonApi\ApiDocumentation\SchemaStore;
use EDT\JsonApi\ApiDocumentation\TagStore;
use EDT\JsonApi\OutputHandling\PropertyReadableTypeProviderInterface;
use EDT\JsonApi\Requests\CreationProcessor;
use EDT\JsonApi\Requests\CreationProcessorConfigInterface;
use EDT\JsonApi\Requests\DeletionProcessor;
use EDT\JsonApi\Requests\DeletionProcessorConfigInterface;
use EDT\JsonApi\Requests\GetProcessor;
use EDT\JsonApi\Requests\GetProcessorConfigInterface;
use EDT\JsonApi\Requests\ListProcessor;
use EDT\JsonApi\Requests\ListProcessorConfigInterface;
use EDT\JsonApi\Requests\UpdateProcessor;
use EDT\JsonApi\Requests\UpdateProcessorConfigInterface;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\JsonApi\Utilities\NameBasedTypeProvider;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use Webmozart\Assert\Assert;

class Manager
{
    /**
     * @var array<non-empty-string, GetableTypeInterface<object>&PropertyReadableTypeInterface<object>>
     */
    protected array $getableTypes = [];
    /**
     * @var array<non-empty-string, ListableTypeInterface<object>&PropertyReadableTypeInterface<object>>
     */
    protected array $listableTypes = [];
    /**
     * @var array<non-empty-string, UpdatableTypeInterface<object>&PropertyReadableTypeInterface<object>>
     */
    protected array $updatableTypes = [];
    /**
     * @var array<non-empty-string, CreatableTypeInterface<object>&PropertyReadableTypeInterface<object>>
     */
    protected array $creatableTypes = [];
    /**
     * @var array<non-empty-string, DeletableTypeInterface>
     */
    protected array $deletableTypes = [];

    /**
     * @var positive-int
     */
    protected int $paginationDefaultPageSize = 20;

    /**
     * @var non-empty-string
     */
    protected string $resourceTypeAttribute = 'resourceType';
    /**
     * @var non-empty-string
     */
    protected string $resourceIdAttribute = 'resourceId';

    public function __construct(
        protected readonly PropertyReadableTypeProviderInterface $typeProvider = new NameBasedTypeProvider()
    ) {}

    /**
     * @param positive-int $size
     */
    public function setPaginationDefaultPageSize(int $size): void
    {
        $this->paginationDefaultPageSize = $size;
    }

    /**
     * Set the attribute key to use to retrieve the resource type string.
     *
     * When defining a Symfony route, you can use placeholders in the URL path, which are automatically available in the
     * corresponding controller action. E.g. `resourceType` and `resourceId` in the following update action.
     * ```
     * #[Route(
     *   path: '/api/{resourceType}/{resourceId}',
     *   name: 'my_update_route',
     *   methods: ['PATCH']
     * )]
     * public function updateAction(string $resourceType, string $resourceId): Response {...}
     * ```
     *
     * You don't need to manually pass the actual type string and id string down to the request handling as they are
     * present in the {@link Request::$attributes attributes of the Request instance}. However, you need to define the
     * attribute names/keys, so they can be retrieved from the Request's attributes array.
     *
     * By default, they are set to `resourceType` and `resourceId`, as shown above.
     *
     * @param non-empty-string $resourceTypeAttribute
     */
    public function setResourceTypeAttribute(string $resourceTypeAttribute): void
    {
        $this->resourceTypeAttribute = $resourceTypeAttribute;
    }

    /**
     * @param non-empty-string $resourceIdAttribute
     *
     * @see {@link setResourceTypeAttribute}
     */
    public function setResourceIdAttribute(string $resourceIdAttribute): void
    {
        $this->resourceIdAttribute = $resourceIdAttribute;
    }

    /**
     * @param GetableTypeInterface<object>&PropertyReadableTypeInterface<object> $type
     */
    public function registerGetableType(GetableTypeInterface $type): void
    {
        $this->addToArray($type, $this->getableTypes);
        $this->typeProvider->addType($type->getTypeName(), $type);
    }

    /**
     * @param list<GetableTypeInterface<object>&PropertyReadableTypeInterface<object>> $types
     */
    public function registerGetableTypes(array $types): void
    {
        array_map($this->registerGetableType(...), $types);
    }

    /**
     * @param ListableTypeInterface<object>&PropertyReadableTypeInterface<object> $type
     */
    public function registerListableType(ListableTypeInterface $type): void
    {
        $this->addToArray($type, $this->listableTypes);
        $this->typeProvider->addType($type->getTypeName(), $type);
    }

    /**
     * @param UpdatableTypeInterface<object>&PropertyReadableTypeInterface<object> $type
     */
    public function registerUpdatableType(UpdatableTypeInterface $type): void
    {
        $this->addToArray($type, $this->updatableTypes);
        $this->typeProvider->addType($type->getTypeName(), $type);
    }

    /**
     * @param CreatableTypeInterface<object>&PropertyReadableTypeInterface<object> $type
     */
    public function registerCreatableType(CreatableTypeInterface $type): void
    {
        $this->addToArray($type, $this->creatableTypes);
        $this->typeProvider->addType($type->getTypeName(), $type);
    }

    public function registerDeletableType(DeletableTypeInterface&NamedTypeInterface $type): void
    {
        $this->addToArray($type, $this->deletableTypes);
        // no need to add it to the type provider, as it is only needed for sparse fieldsets,
        // i.e. responses potentially containing resources
    }

    /**
     * @param list<ListableTypeInterface<object>&PropertyReadableTypeInterface<object>> $types
     */
    public function registerListableTypes(array $types): void
    {
        array_map($this->registerListableType(...), $types);
    }

    /**
     * @param list<UpdatableTypeInterface<object>&PropertyReadableTypeInterface<object>> $types
     */
    public function registerUpdatableTypes(array $types): void
    {
        array_map($this->registerUpdatableType(...), $types);
    }

    /**
     * @param list<CreatableTypeInterface<object>&PropertyReadableTypeInterface<object>> $types
     */
    public function registerCreatableTypes(array $types): void
    {
        array_map($this->registerCreatableType(...), $types);
    }

    /**
     * @param list<DeletableTypeInterface&NamedTypeInterface> $types
     */
    public function registerDeletableTypes(array $types): void
    {
        array_map($this->registerDeletableType(...), $types);
    }

    /**
     * Register a type whose resources should not be directly available via `get`, `list`, `create`, `update` or
     * `delete`, but can still be included in a response beside the primary resources.
     *
     * Note that these types are still available via the relationships pointing to them. I.e. if `Book` resources
     * can be fetched directly via `list` request and have a relationship to `Author` resources, then it may be possible
     * to include the authors in the response, even if the `Author` resource type was not registered with this method.
     * However, to ensure a consistent behavior in case of the usage of sparse fieldsets too, you must register them via
     * this method.
     *
     * Do not call this method if your type is registered as directly available via the other register methods.
     *
     * @param PropertyReadableTypeInterface<object>&NamedTypeInterface $type
     */
    public function registerType(PropertyReadableTypeInterface&NamedTypeInterface $type): void
    {
        $this->typeProvider->addType($type->getTypeName(), $type);
    }

    /**
     * Register types whose resources should not be directly available via `get`, `list`, `create`, `update` or
     * `delete`, but can still be included in a response beside the primary resources.
     *
     * Note that these types are still available via the relationships pointing to them. I.e. if `Book` resources
     * can be fetched directly via `list` request and have a relationship to `Author` resources, then it may be possible
     * to include the authors in the response, even if the `Author` resource type was not registered with this method.
     * However, to ensure a consistent behavior in case of the usage of sparse fieldsets too, you must register them via
     * this method.
     *
     * Do not call this method if your types are registered as directly available via the other register methods.
     *
     * @param list<PropertyReadableTypeInterface<object>&NamedTypeInterface> $type
     */
    public function registerTypes(array $type): void
    {
        array_map($this->registerType(...), $type);
    }

    /**
     * Creates an instance to generate an {@link OpenAPI} schema from the types set in this instance so far.
     *
     * To activate the generation of documentation for specific actions, make sure to set the corresponding
     * configurations (e.g. {@link OpenApiDocumentBuilder::setGetActionConfig()} and
     * {@link OpenApiDocumentBuilder::setListActionConfig()}).
     */
    public function createOpenApiDocumentBuilder(): OpenApiDocumentBuilder
    {
        return new OpenApiDocumentBuilder(
            new SchemaStore(),
            new TagStore(),
            $this->paginationDefaultPageSize,
            $this->getableTypes,
            $this->listableTypes,
        );
    }

    public function createGetProcessor(GetProcessorConfigInterface $config): GetProcessor
    {
        return new GetProcessor(
            $config->getEventDispatcher(),
            $config->getResponseFactory($this->typeProvider),
            $this->getableTypes,
            $this->resourceTypeAttribute,
            $this->resourceIdAttribute
        );
    }

    public function createListProcessor(ListProcessorConfigInterface $config): ListProcessor
    {
        return new ListProcessor(
            $config->getFilterTransformer(),
            $config->getFilterValidator(),
            $config->getSortingTransformer(),
            $config->getSortingValidator(),
            $config->getPagPaginatorTransformer($this->paginationDefaultPageSize),
            $config->getPaginatorFactory(),
            $config->getSchemaPathProcessor(),
            $config->getEventDispatcher(),
            $config->getResponseFactory($this->typeProvider),
            $this->listableTypes,
            $this->resourceTypeAttribute,
        );
    }

    public function createCreationProcessor(CreationProcessorConfigInterface $config): CreationProcessor
    {
        return new CreationProcessor(
            $config->getEventDispatcher(),
            $config->getResponseFactory($this->typeProvider),
            $config->getValidator(),
            $config->getRequestConstraintFactory(),
            $this->creatableTypes,
            $this->resourceTypeAttribute,
            $this->resourceIdAttribute,
            $config->getMaxBodyNestingDepth()
        );
    }

    public function createUpdateProcessor(UpdateProcessorConfigInterface $config): UpdateProcessor
    {
        return new UpdateProcessor(
            $config->getValidator(),
            $config->getEventDispatcher(),
            $config->getResponseFactory($this->typeProvider),
            $config->getRequestConstraintFactory(),
            $this->updatableTypes,
            $this->resourceTypeAttribute,
            $this->resourceIdAttribute,
            $config->getMaxBodyNestingDepth()
        );
    }

    public function createDeletionProcessor(DeletionProcessorConfigInterface $config): DeletionProcessor
    {
        return new DeletionProcessor(
            $config->getEventDispatcher(),
            $this->deletableTypes,
            $this->resourceTypeAttribute,
            $this->resourceIdAttribute
        );
    }

    /**
     * @template T of NamedTypeInterface
     *
     * @param T $type
     * @param array<non-empty-string, T> $array
     *
     * @return array<non-empty-string, T>
     */
    protected function addToArray(NamedTypeInterface $type, array &$array): array
    {
        $typeName = $type->getTypeName();
        Assert::keyNotExists($array, $typeName);
        $array[$typeName] = $type;

        return $array;
    }
}
