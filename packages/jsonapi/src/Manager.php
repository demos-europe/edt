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
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use InvalidArgumentException;

/**
 * This class attempts to provide an easy (i.e. high level) entry point into the library's API capabilities.
 *
 * The intended usage is as follows:
 *
 * 1. Create an instance of this class
 * 2. Register your types into it, to expose them to potential clients
 * 3. (Optionally) create an OpenApi document, containing the capabilities of your API
 * 4. React to a client request by retrieving the processor corresponding to the kind of request and passing control to it
 *
 * In the following sections each step listed above is explained in more detail.
 *
 * **1. Create an instance of this class**
 *
 * Due to implementation details, this class will store registered types in different manners, but currently only one
 * of those multiple "storage" implementation can be
 * adjusted via the constructor.
 * However, the default implementation should be sufficient in most cases anyway.
 *
 * Please note that if you want to create sets of different types, you'd create a separate manager instance for each set.
 * For example this may be useful if you have predefined user authorizations, with different users having access to different
 * types.
 * E.g. for users with the role `administrator` you'd use a corresponding `$adminManager`, into which you registered all types.
 * On the other hand, for users with the role `guest` you'd use a corresponding `$guestManager`, into which you registered
 * only specific types, that should be exposed to guest users.
 * For more dynamic authorizations you may have to create and fill a new manager instance for each request.
 *
 * **2. Register your types into it**
 *
 * After initializing an instance of this class, you can use it to register type instances of varying implementations.
 * The registration methods are specific to a capability.
 * E.g. the {@link self::registerGetableType()} method requires a type that can be used to process JSON:API `get` requests,
 * but it will not investigate the given type further nor automatically register it as listable, even if its implementation
 * indeed inherits from {@link ListableTypeInterface}.
 * If you want to register that type as listable as well, you have to call {@link self::registerListableType()} with the same instance manually.
 *
 * This allows fine-grained control over which types you want to support (i.e. expose) when processing different requests like `get` or `list`.
 * To create different sets of registered types, you'd create a separate instance of this class for each set, as explained above.
 *
 * The manager is indifferent as to how a type instance was created.
 * It may
 *
 * * be a custom implementation directly implementing the interface required by the registration method
 * * extend from {@link AbstractResourceType}
 * * have been created via one of the {@link ResourceConfigBuilderInterface}
 * * have been created by any other means
 *
 * **3. (Optionally) create an OpenApi document**
 *
 * You may want to expose your APIs capabilities via an OpenApi page.
 * To do so, you can use {@link self::createOpenApiDocumentBuilder()} and build an OpenApi
 * document, containing the capabilities provided by your API, based on the types you've registered into the manager instance.
 * The document instance can then be used by you in any manner you like, e.g. building a web page presenting the API to potential clients.
 *
 * *NOTE (#134): Currently only getable and listable types are considered by the OpenApi document builder, even if you registered additional types like deletable.*
 *
 * **4. React to a client request**
 *
 * After registering your type instances into the manager, you can create processors for each kind of request as needed.
 * E.g. the {@link self::createGetProcessor()} method can be used to react to JSON:API `get` requests. 
 * The following list shows the supported kind of requests:
 *
 * * `get`: {@link self::createGetProcessor()}
 * * `list`: {@link self::createListProcessor()}
 * * `create`: {@link self::createCreationProcessor()}
 * * `update`: {@link self::createUpdateProcessor()}
 * * `delete`: {@link self::createDeletionProcessor()}
 *
 * As mentioned previously, no processor will be automatically created nor will become automatically active when a request is
 * received.
 * Instead, you have to determine the kind of request you want to handle and retrieve the corresponding processor.
 * With the actual request already present as {@link Request} instance, you can then pass control to that processor.
 */
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

    /**
     * @param PropertyReadableTypeProviderInterface $typeProvider this instance will be automatically filled as types are registered into the manager
     */
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
     *
     * **NOTE (#134): Currently only getable and listable types are supported (i.e. considered) when building the OpenApi document.**
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

    /**
     * Create a processor to handle JSON:API `get` requests.
     *
     * The returned instance will support access to the types currently registered as getable in this manager instance.
     */
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

    /**
     * Create a processor to handle JSON:API `list` requests.
     *
     * The returned instance will support access to the types currently registered as listable in this manager instance.
     */
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

    /**
     * Create a processor to handle JSON:API `create` requests.
     *
     * The returned instance will support access to the types currently registered as creatable in this manager instance.
     */
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

    /**
     * Create a processor to handle JSON:API `update` requests.
     *
     * The returned instance will support access to the types currently registered as updatable in this manager instance.
     */
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

    /**
     * Create a processor to handle JSON:API `delete` requests.
     *
     * The returned instance will support access to the types currently registered as deletable in this manager instance.
     */
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
     * Add the given type to the given array, using the result of {@link NmaedTypeInterface::getTypeName()} as array key.
     *
     * The type will be added directly to the given array reference, i.e. using the return is optional.
     *
     * @template T of NamedTypeInterface
     *
     * @param T $type the type to be added to the given array
     * @param array<non-empty-string, T> $array the array to add the given type to
     *
     * @return array<non-empty-string, T> the given array, now containing the given type
     *
     * @throws InvalidArgumentException if the corresponding array key is already in use
     */
    protected function addToArray(NamedTypeInterface $type, array &$array): array
    {
        $typeName = $type->getTypeName();
        Assert::keyNotExists($array, $typeName);
        $array[$typeName] = $type;

        return $array;
    }
}
