# Changelog

## Unreleased

### BC BREAK: The `GetRequest` and `DeletionRequest` constructors no longer take a `RequestTransformer` instance

Previously the `RequestTransformer` instance was not actually used anyway in the implementation of `GetRequest` and `DeletionRequest`.
Hence, the constructors was adjusted and do no longer accept such instance.

If you call the constructors manually or define the parameter manually in your dependency injection configuration (e.g. in Symfony), you need to adjust these places. If you extend from `GetRequest` or `DeletionRequest`, you also need to adjust the child class constructors that call the constructor as their parent.

### BC BREAK: fix OpenApi generator and rework it for generic usage

Multiple problems existed in the `OpenAPISchemaGenerator` class.
1. erroneous: recursion loop for bidirectional relationships
2. hardcoded: coupling to specific translation keys
3. limited: no distinction between different kind of exposures (get/list/create/update/delete)

To mitigate these problems, the class and its usage was reworked.
The class itself was renamed to `OpenApiDocumentBuilder`, but it should now be used via the newly introduced `Manager` class anyway.

Previously you would initialize `OpenAPISchemaGenerator` yourself and retrieve an `OpenApi` instance (containing the OpenApi document data) via `getOpenAPISpecification`.
Now you create a `Manager` instance and fill it with the types that should be directly accessible (i.e. not only reachable via includes).
Afterward its `Manager::createOpenApiDocumentBuilder` can be used to retrieve an `OpenApiDocumentBuilder`.
In that `OpenApiDocumentBuilder` instance, you can set configurations for actions you are interested in (e.g. `get` and `list`).
After the configuration of the builder, you can call `OpenApiDocumentBuilder::buildDocument` to retrieve the `OpenApi` instance.

This seemingly increased complexity is mainly the result of keeping the generation more generic, without assuming specific translation keys to exist.
Besides that, it also allows to re-usage the same instance to generate the documentation in different languages and introduces the `Manager` class as future major entry point into the library.

The following shows the adjustment needed to mitigate from the old approach to the new one.

#### Old approach

Note that no distinction was possible between types available via `get` and `list`. I.e. it was not possible to expose a type for JSON:API `get` actions only, as it was not possible to expose it for JSON:API `list` actions only. If either exposure was wanted, the documentation would state the type as exposed with the other action too.
Also, the `OpenApiSchemaGenerator` implementation would simply assume specific translation keys to be available via the given translator.

```php
$types = [
    /* your resource type instances, that shall be directly accessible via JSON:API `get` or `list` */ 
];

$schemaGenerator = new \EDT\JsonApi\ApiDocumentation\OpenApiSchemaGenerator(
    $types,
    $router, // \Symfony\Component\Routing\RouterInterface
    new \EDT\JsonApi\ApiDocumentation\SchemaStore(),
    $translator, // \Symfony\Contracts\Translation\TranslatorInterface
    $defaultPageSize
);

$openApiDocument = $schemaGenerator->getOpenAPISpecification();
```

#### New approach

To keep the migration easy, the old behavior can be configured using prepared configuration classes as shown below.
Those are however set as deprecated from the start, as applications should provide their own implementation instead.

```php
$getableTypes = [
    /* your resource type instances that shall be accessible via JSON:API `get` actions */
];
$listableTypes = [
    /* your resource type instances that shall be accessible via JSON:API `list` actions */
];

$manager = new \EDT\JsonApi\Manager();
$manager->setPaginationDefaultPageSize($defaultPageSize);
$manager->addGetableTypes($getableTypes);
$manager->addListableTypes($listableTypes);
$schemaGenerator = $manager->createOpenApiDocumentBuilder();
$schemaGenerator->setGetActionConfig(
    new \EDT\JsonApi\ApiDocumentation\GetActionConfig($router, $translator)
);
$schemaGenerator->setListActionConfig(
    new \EDT\JsonApi\ApiDocumentation\ListActionConfig($router, $translator)
);
$schemaGenerator->buildDocument(new \EDT\JsonApi\ApiDocumentation\OpenApiWording($translator));
```

### BC BREAK: Allow to pass `null` paths into `DrupalConditionFactoryInterface::createConditionWithoutValue` and `createConditionWithValue`

If you did not override these two methods, you don't need to do anything.
If you did override either of them, you need to adjust the signatures of your overriding method to accept not only `array` as path but also `null`.
Accordingly, your logic needs to handle a `null` value as path.
However, it is valid to just throw an exception if your implementation does not support it.

### BC BREAK: Adjust constructor of `DynamicTransformer` to take some specific values instead of a whole `TransferableTypeInterface` instance

Previously the `DynamicTransformer` constructor required an instance of `TransferableTypeInterface` from which it retrieved data internally.
Now the constructor does no longer accept a `TransferableTypeInterface` instance, but requires the values it retrieved internally instead.
I.e., instead of calling
```php
$transformer = new \EDT\JsonApi\OutputHandling\DynamicTransformer(
    $type
);
```
you can now call it like this:
```php
$transformer = new \EDT\JsonApi\OutputHandling\DynamicTransformer(
    $type->getTypeName(),
    $type->getEntityClass(),
    $type->getReadability(),
);
```

### BC BREAK: Require the implementation of five other methods instead of `getResourceConfig` in children of `AbstractResourceType`

Previously, the `AbstractResourceType` implementation did define the abstract method `getResourceConfig(): ResourceConfigInterface` to implement multiple methods defined by its parent interfaces by itself.
Classes extending from `AbstractResourceType` were required to implement the `getResourceConfig` method.

Instead, the `AbstractResourceType` now leaves the implementation of the parent interface methods to extending classes and thus does no longer define `getResourceConfig` at all.

To keep the previous behavior, the following code can be added to the classes directly extending from `AbstractResourceType`:

```php
public function getReadability(): ResourceReadability
{
    return $this->getResourceConfig()->getReadability();
}

public function getFilteringProperties(): array
{
    return $this->getResourceConfig()->getFilteringProperties();
}

public function getSortingProperties(): array
{
    return $this->getResourceConfig()->getSortingProperties();
}

public function getUpdatability(): ResourceUpdatability
{
    return $this->getResourceConfig()->getUpdatability();
}

protected function getInstantiability(): ResourceInstantiability
{
    return $this->getResourceConfig()->getInstantiability();
}
```

### BC BREAK: Replace `RequiredRelationshipConstructorBehavior` constructor boolean parameter with `Cardinality` enum

Instead of taking a simple `bool $toOne` parameter in the constructor, you now need to use the `Cardinality` enum. I.e. calls with `true` needs to be replaced with `Cardinality::TO_ONE` and calls with `false` needs to be replaced with `Cardinality::TO_MANY`.

### BC BREAK: Remove `*ConstructorBehaviorFactory` classes, you can use added static `createFactory` methods in the corresponding `*Behavior` classes instead

When configuring resource properties you can connect them to behaviors, which is done by passing a factory instance into the corresponding property config method, which is internally used to create the behavior to be connected.
There are many different kind of properties with different behavior implementations provided by the library, which required many corresponding factory classes.
To make the behavior configuration more approachable, all factory class implementations were moved as anonymous classes into their corresponding behavior, hidden from the configuring developer.
E.g. instead of using
```php
$config->title->addConstructorBehavior(
    new AttributeConstructorBehaviorFactory(null, null));
```
you would now use
```php
$config->title->addConstructorBehavior(
    AttributeConstructorBehavior::createFactory(null, OptionalField::NO, null));
```

The additional `OptionalField::NO` parameter is independent of this change and explained further down below.

To mitigate to the new approach, search in your application for all usages of classes that end with `ConstructorBehaviorFactory`.
For each one, a corresponding class ending with `ConstructorBehavior` exists, providing a `createFactory` method, as shown above.

### BC BREAK: Adjust `*SetBehavior` and `*SetBehaviorFactory` class constructors to take the `OptionalField` enum instead of `bool`

Previously, some classes ending with the name `SetBehavior` or `SetBehaviorFactory` took a boolean to imply if the properties they were configured for are required in the request data or not.
Now creating instances of these classes requires an `OptionalField` enum instead.
I.e. a call with `true` is to be replaced with `OptionalField::YES`.
A call with `false` is to be replaced with `OptionalField::NO`.

### BC BREAK: Adjust `*Readability` class constructors to take `DefaultField` instead of `bool`

Previously, some classes ending with the name `Readability` took a boolean to imply if the corresponding property should be present in the response if no fieldset was specified in the request.
Now creating instances of these classes requires a `DefaultField` enum instead.
I.e. a call with `true` is to be replaced with `DefaultField::YES`.
A call with `false` is to be replaced with `DefaultField::NO`.

### BC BREAK: Remove `ReadablePropertyConfigBuilderInterface`

Its only method `readable` is still present in all of its former child classes and interfaces.
The root interface in which the signature is defined is now `AttributeOrRelationshipBuilderInterface`.
If you used `ReadablePropertyConfigBuilderInterface` directly in your application you need to adjust your inheritance hierarchy.
If you did not use `ReadablePropertyConfigBuilderInterface` directly, you don't need to do anything.

### Remove unnecessary template parameters from `FilteringTypeInterface`

If you used `FilteringTypeInterface` directly in your application you can now remove the previously necessary template parameters. If you did not use the interface directly or did not specify its template parameters anyway, you don't need to do anything.

Using the wrong template parameters will not affect your application at runtime.
However, on certain phpstan levels, the static code checker may raise concerns.

## 0.24.42 - 2024-02-13

* feature: add classes to programmatically create Drupal filters
* feature: provide assertion methods to subclasses of `PredefinedDrupalConditionFactory`
* refactor: (bc break) improve return of `PredefinedDrupalConditionFactory::getSupportedOperators`, instead of just the names of the supported operators, it is able to provide additional `Constraint`s too; however, this remains currently unused and unsupported; apply `array_keys` to the return value as quick migration
* refactor: (bc break) split `PredefinedDrupalConditionFactory::getOperatorFunctions` into `getOperatorFunctionsWithValue` and `getOperatorFunctionsWithoutValue`; overriding implementations must be adjusted accordingly
* refactor: (bc break) start to separate validation from filter transformation; `DrupalFilterParser::parseFilter` will no longer automatically validate the given filter, add a call to `DrupalFilterParser::validateFilter` as quick migration
* refactor: introduce operator constants in `StandardOperator` and prefer them over string usage
* refactor: introduce and use `ExpectedPropertyCollectionInterface`

## 0.24.41 - 2024-01-17

* feature: add early break, to potentially improve performance depending on `RepositoryInterface` implementation

## 0.24.40 - 2024-01-16

* feature: allow setting identifiers, attributes or relationships as readable, filterable or sortable by default

## 0.24.39 - 2024-01-12

* feature: relax `composer.json` dependencies to allow Symfony 6 (experimental)
* fix: use correct comparison for 0.24.37 fix

## 0.24.37 - 2024-01-12

* fix: provide client with backend-created IDs

## 0.24.36 - 2024-01-10

* refactor: allow to change max request body JSON nesting depth via injection
* rollback: lock pagination in list requests to page-based again, as others are not yet supported
* feature: make attribute validation more flexible
  * to allow arrays as attributes, the `RequestConstraintFactory` must now be configured to define how they should be handled

## 0.24.35 - 2024-01-09

* fix: increase JSON request body max depth to allow for non-primitive attributes (i.e. array structures)

## 0.24.33 - 2024-01-09

* fix: handle a to-one relationship with no items correctly on update and creation requests

## 0.24.32 - 2024-01-05

* fix: handle required properties correctly in more validation places
* refactor: allow `array` as attribute type, but for string lists only for now
* refactor: improve some validation messages
* feature: use Symfony validators to validate `sort` query parameter
  * `ListRequest` now requires `SortValidator` as constructor parameter

## 0.24.31 - 2024-01-05

* fix: handle required properties correctly in validation

## 0.24.30 - 2023-12-21

* feature: provide request body in creation and update events

## 0.24.29 - 2023-12-12

* feature: rework "instance of" implementation to be properly usable (though support for execution in `ConditionEvaluator` is still missing)

## 0.24.28 - 2023-12-11

* feature: add `IsTargetEntityNotInstanceOf` clause

## 0.24.27 - 2023-12-11

* fix: solve logical problem in `IsInstanceOfTargetEntity` and rename it to `IsTargetEntityInstanceOf`

## 0.24.26 - 2023-12-11

* feature: provide target entity alias to clauses
* feature: add `IsInstanceOfTargetEntity` condition

## 0.24.23 - 2023-12-01

* fix: do not add unnecessary `use` statements when generating classes

## 0.24.22 - 2023-12-01

* feature: allow to used different entity references in property types when generating classes
* feature: allow to disable comment generation for properties when generating classes

## 0.24.21 - 2023-12-01

* fix: solve various simple bugs

## 0.24.20 - 2023-11-30

* fix: enable filtering for resource identifier set as filterable
* fix: create property builder correctly

## 0.24.19 - 2023-11-30

* fix: do not automatically create config builders for all properties detected by `MagicResourceConfigBuilder`, as otherwise unused relationships will have no relationship type set when needed

## 0.24.18 - 2023-11-30

* fix: check array size correctly
* refactor: provide `getFullyQualifiedName` and `getTemplateParameter` methods in all `TypeInterface` implementations, adjust usages accordingly
* fix: add support for non-classes/non-interfaces to `DocblockPropertyByTraitEvaluator`

## 0.24.16 - 2023-11-30

* feature: improve docblock parsing support, Types in tags like `propert-read` now support more cases of template parameter usages
* refactor: move `TypeInterface` and its implementations in different namespace
* refactor: reduce phpstan concerns

## 0.24.15 - 2023-11-29

* fix: consider type correctly in assertion

## 0.24.14 - 2023-11-29

* fix: add missing method call

## 0.24.13 - 2023-11-29

* fix: implement necessary interface
* fix: add missing type check

## 0.24.12 - 2023-11-29

* fix: use correct return type

## 0.24.11 - 2023-11-29

* feature: add `Iso8601PropertyAccessor` to automatically convert Doctrine `datetime` to ISO8601 strings 

## 0.24.10 - 2023-11-29

* feature: move logic into separate functions to improve support for adjustments in child classes

## 0.24.8 - 2023-11-28

* feature: when setting to-many relationship properties with `ProxyPropertyAccessor`, automatically convert `array`s to Doctrine `Collection`s
* refactor: `PropertySetBehaviorInterface::executeBehavior` must now return the list of properties that were not adjusted according to the request instead of the previously returned `bool` that implied if any properties were not adjusted according to the request
  * due to these changes the following classes will now expect callables in their constructors returning such list:
    * `FixedSetBehavior`
    * `CallbackAttributeSetBehaviorFactory`
    * `CallbackAttributeSetBehavior`
    * `CallbackToOneRelationshipSetBehaviorFactory`
    * `CallbackToOneRelationshipSetBehavior`
    * `CallbackToManyRelationshipSetBehaviorFactory`
    * `CallbackToManyRelationshipSetBehavior`
  * due to these changes the `initializable` and `updatable` methods of the following classes will now expect (still optional) callables which returns such lists
    * `AttributeConfigBuilderInterface`
    * `ToOneRelationshipConfigBuilderInterface`
    * `ToManyRelationshipConfigBuilderInterface`
* refactor: for similar reasons as stated above, the `callable` parameters in following classes must now include a list of properties that were not set according to the request in their return
  * `AttributeConstructorBehaviorFactory`
  * `AttributeConstructorBehavior`
  * `FixedConstructorBehaviorFactory`
  * `RequiredRelationshipConstructorBehavior`
  * `RequiredToOneRelationshipConstructorBehaviorFactory`
  * `ToManyRelationshipConstructorBehavior`
  * `ToManyRelationshipConstructorBehaviorFactory`
  * `ToOneRelationshipConstructorBehavior`
  * `ToOneRelationshipConstructorBehaviorFactory`

## 0.24.4 - 2023-10-20

* feature: add support for general update behaviors
* feature: add support for general creation behaviors
* feature: allow creation-configuration of ID config builders
* feature: add support for redundant attributes/annotations
* refactor: use interfaces instead of callables

## 0.24.3 - 2023-10-16

* feature: improve exception message

## 0.24.2 - 2023-10-16

* fix: generate correct docblock `param` tag

## 0.24.1 - 2023-10-16

* feature: restore previously removed exception methods for backward compatibility

## 0.24.0 - 2023-10-16

* refactor: refactor: restructure classes and API for resource property configuration

## 0.23.0 - 2023-09-19

* refactor: apply object-oriented approach for entity modification
  * consolidate up class/interface naming
  * provide entity changing instances with more general data, thus allowing more abstraction and moving specific behavior into objects
  * split constructor argument instances from setability instances for entity initialization
  * add convenience methods to `WrapperObject`
  * adjust `PropertyBuilder` (now `PropertyConfig`) API
* fix: allow `id` and `type` fields in update/creation request, as originally intended
* refactor: setting relationship properties as default include when `readable` must now be done on `readable` call and takes the place of the previously removed `bool` to disable sanity checks
* feature: add new approach to configure resource properties

## 0.22.3 - 2023-08-14

* feature: preserve context information in exception

## 0.22.2 - 2023-08-08

* fix: use correct template parameter

## 0.22.1 - 2023-08-08

* fix: consider access conditions when deleting resources

## 0.22.0 - 2023-08-08

* refactor: adjust exceptions, interfaces and events

Simplify exception handling in requests: Wrapping every
exception to provide information the caller already has
is unnecessarily complicated.

Add events (again): Events to execute code before or after
request handling were added. These will probably be the
target of heavy refactoring in the foreseeable future.

Restructure/rename interfaces/namespaces: Using type
interfaces directly corresponding to requests should make the
hierarchy more understandable. The namespaces were adjusted
for clarity.

## 0.21.4 - 2023-08-07

* feature: allow read usage of `DocblockTagParser` properties

## 0.21.3 - 2023-08-07

* refactor: allow child classes of `ClauseFunction` and `OrderBy` interface in `DoctrineOrmEntityProvider` template parameters

## 0.21.2 - 2023-08-07

* refactor: use reflection class as parameter instead of class name

## 0.21.1 - 2023-08-07

* feature: allow classes extending from `ReflectionSegmentFactory` to reuse parts of its logic

## 0.21.0 - 2023-08-01

* refactor: revoke previously added event support in favor of objects 

## 0.20.0 - 2023-07-24

* feature: integrate event dispatching for resource creation and update into `AbstractResourceType`; implementation of `getEventDispatcher` is now required 

## 0.19.0 - 2023-06-20

* refactor: require `AbstractResourceType::getIdentifierPropertyPath` to be implemented
* refactor: restructure the "Type" interface hierarchy
* refactor: rename `AbstractResourceType::getAccessCondition` to `getAccessConditions` and return list of conditions instead of a single (already merged) one
* feature: apply more centralized and stricter request validation
* refactor: remodel logic to be more flexible for different use cases
* refactor: conditions/sort methods returned by `getAccessCondition`/`getDefaultSortMethods` must now access the schema of the backing entity instead of the schema of the type (i.e. the automatic de-aliasing was removed)
* feature: a type property defined in `getFilterableProperties` and `getSortableProperties` can now use different property in the backing entity for filtering and sorting 
* refactor: use type readabilities/updatabilities/initializabilities instances instead of using a predefined algorithm
* refactor: separate `id` property from attributes; attributes MUST NOT return an `id` property but implement `IdentifiableTypeInterface::getIdentifierReadability` instead
* fix: use correct property list when reading to-one relationships in `WrapperObject`

## 0.17.4 - 2023-02-15

* refactor: require enum to define relevant tags on docblock parsing 
* fix: allow `DocblockTagParser` to find interfaces
* fix: allow creation of `IS NULL` and `IS NOT NULL` Drupal conditions

## 0.17.2 - 2023-02-01

* fix: update monorepo dependencies to solve monorepo-split bug
* fix: update `thecodingmachine/safe` dependency for PHP 8 support
* refactor: update dev-dependencies

## 0.17.1 - 2023-01-30

* fix: update `composer.json` files

## 0.17.0 - 2023-01-30

* remove `getTransformer` from `ResourceTypeInterface`, transformation is now always done via `DynamicTransformer`, regardless of `TypeInterface` implementation, thus
  * remove `getReadableResourceTypeProperties`, `getWrapperFactory`, `getLogger` and `getMessageFormatter` from `ResourceTypeInterface`/`AbstractResourceType`
  * the `WrapperObjectFactory`, `LoggerInterface` and `MessageFormatter` implementations returned by the methods above now need to be injected into the `AbstractApiService`
* remove static `ResourceTypeInterface::getName()` method, the non-static `TransferableTypeInterface::getIdentifier()` method needs to be implemented instead
* feature: rework resource property configuration
  * `CreatableTypeInterface`:
      * remove `getPropertiesRequiredForCreation`, its information is now available via `getInitializableProperties`
      * change return type of `getInitializableProperties`
  * change signature and return type of `TransferableTypeInterface::getUpdatableProperties`; when extending from `AbstractResourceType`, updatability should be configured via `configureProperties()` now instead of overriding `getUpdatableProperties` 
  * `UpdatableRelationship`:
      * split class into `AttributeUpdatability`/`ToOneRelationshipUpdatability`/`ToManyRelationshipUpdatability` depending on the context
      * change constructor parameters
      * rename `getValueConditions()` method
      * allow to use custom write functions
  * disallow `null` as `$value` in `CachingPropertyReader::determineToOneRelationshipValue`
  * partially validate values read from entities in `WrapperArrayFactory` and `WrapperObject` against configuration retrieved from type instances, if they don't match expectations an exception is thrown
  * respect `Updatability` entity conditions when updating values in `WrapperObject`
  * respect `Updatability` value conditions when updating relationship values (conditions for attribute values are not yet supported and will be ignored)
  * `DynamicTransformer`
      * couple to `ResourceTypeInterface`, to reduce complexity
      * change constructor parameters
      * validate `transform` `$entity` parameter
      * partially validate attribute and relationship values read from `$entity`
      * validate `include…` parameters
  * `AbstractResourceType`
      * change `getUpdatableProperties` default implementation from returning an empty array to respecting the settings one via `configureProperties`
      * change return type and signature of `processProperties`
      * remove `getTypeProvider`
      * remove `getPropertyCollection`, implement `configureProperties` instead
  * reduce caching done in `CachingResourceType`
* ease initialization of valid path segments with factories by accepting `PropertyPathInterface` instances, a single non-empty-string or a list of non-empty-strings

## 0.16.1 - 2022-11-15

- feature: allow the usage of `PropertyPathInterface` to create sort methods

## 0.16.0 - 2022-11-15

- refactor: adjust `PathTransformer::prefixPathsList` signature
- refactor: use more specific type-hints where possible
- refactor: switch the parameter order of the callables returned by `DrupalConditionFactoryInterface`
- refactor: change the path parameter type to create conditions from `non-empty-string $property, non-empty-string $properties` to `non-empty-list<non-empty-string>|PropertyPathInterface $properties`
- refactor: change the path parameter type to create sort methods from `non-empty-string $property, non-empty-string $properties` to `non-empty-list<non-empty-string> $properties`
- feature: add minimal validation for the type of the value passed via the `value` field in a Drupal filter condition

## 0.15.0 - 2022-11-13

- feature: add JSON:API sort format validation class
- fix: use correct property path delimiter
- fix: disallow `null` conjunction in `DrupalFilterValidator`
- refactor: `getAsNames`/`getAsNamesInDotNotation` in `PropertyAutoPathTrait` and `PropertyAutoPathInterface` must not return an empty list/empty-string respectively; initializing instances with an empty property path is allowed, but ensure they are not used for anything else but further path-building
- refactor: require implementations using `PropertyAutoPathTrait` to also implement `PropertyAutoPathInterface`
- refactor: assert `ExposableRelationshipTypeInterface` implementation and its `isExposedAsRelationship` for `true` in `AbstractResourceType` for `getReadableProperties`, `getSortableProperties` and `getFilterableProperties` but almost nowhere else
- refactor: require `TransferableTypeInterface` to create `WrapperObject` instances
- refactor: change methods and signatures in `AbstractProcessorConfig` and remove `PropertyPathProcessor::getPropertyTypeIdentifier`
- refactor: require `TransferableTypeInterface::getReadableProperties` to return `TransferableTypeInterface` instances
- refactor: require `TypeInterface::getInternalProperties` to return `TypeInterface` instances
- refactor: require `SortableTypeInterface::getSortableProperties` to return `SortableTypeInterface` instances
- refactor: require `FilterableTypeInterface::getFilterableProperties` to return `FilterableTypeInterface` instances
- refactor: require `TransferableTypeInterface::getUpdatableProperties` to return `UpdatableRelationship` instances
- refactor: merge `ReadableTypeInterface` and `UpdatableTypeInterface` into `TransferableTypeInterface`
- refactor: remove obsolete `TypeAccessor`
- refactor: avoid type identifier in `PropertyPathProcessor`

## 0.14.1 - 2022-11-11

- feature: attempt parallel PHP 8 support

## 0.14.0 - 2022-11-07

- feature: add `PropertyBuilder::getName()` method
- refactor: deprecate `ExposableRelationshipTypeInterface::isExposedAsRelationship`, evaluate the conditions when returning relationships in methods like `ReadableTypeInterface::getReadableProperties()` instead

## 0.13.2 - 2022-10-31

- refactor: adjust `CreatableTypeInterface` template parameters
- refactor: remove `TypeRetrievalAccessException` static constructors: `unknownTypeIdentifier`, `noNameWithImplementation`, `typeExistsButNotAvailable` and `typeExistsButNotReferencable`
- refactor: require `ResourceTypeInterface` to implement `ExposableRelationshipTypeInterface` and `ExposablePrimaryResourceTypeInterface`, each should not only correspond to `isReferencable`/`isDirectlyAccessible` respectively but include the logic in `isAvailable` too
- fix: use stricter path processing; every relationship in paths used by external callers must now return `true` in `ExposableRelationshipTypeInterface::isExposedAsRelationship`; this for example affects type wrappers (`WrapperObjectFactory`/`WrapperArrayFactory`) and JSON:API filtering, reading and sorting
- refactor: remove `TypeInterface::isAvailable`, use `ExposableRelationshipTypeInterface::isExposedAsRelationship` or `ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource` instead, `AbstractApiService` was adjusted to require `isExposedAsPrimaryResource` to return `true` for the primary accessed resource types
- refactor: remove `TypeInterface::isAvailable` requirement from type wrapper factories (`WrapperObjectFactory`/`WrapperArrayFactory`), calls must decide by themselves on the restriction of the root type (e.g. `ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource`), relationships will automatically be checked for `true` return in `ExposableRelationshipTypeInterface::isExposedAsRelationship`
- refactor: remove `TypeInterface::isDirectlyAccessible`, `ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource` can be used instead
- refactor: remove `TypeInterface::isReferencable`, `ExposableRelationshipTypeInterface::isExposedAsRelationship` can be used instead
- refactor: on external accesses to filterable, readable and sortable properties, require the corresponding type to be an exposed relationship

## 0.13.1 - 2022-10-31

- refactor: rename `AbstractTypeAccessor` to `AbstractProcessorConfig`
- refactor: rename `AbstractTypeAccessor::getType` to `getRelationshipType`
- refactor: require `array` instead of varargs for `PropertyPath` initialization

## 0.13.0 - 2022-10-31

- refactor: use PHP 7.4 property types where possible
- chore: require at least PHP 7.4 as dependency

## 0.12.23 - 2022-10-31

- refactor: remove deprecated `SchemaPathProcessor::mapSortMethods`, use `SchemaPathProcessor::processDefaultSortMethods()` and `SchemaPathProcessor::mapSorting()` instead
- refactor: remove deprecated `SchemaPathProcessor::mapConditions`, use `SchemaPathProcessor::mapFilterConditions()` and `SchemaPathProcessor::processAccessCondition()` instead
- refactor: remove deprecated `TypeRestrictedEntityProvider`, use the individual components manually and optimize them for your use-case
- refactor: remove deprecated `GenericEntityFetcher`, use the individual components manually and optimize them for your use-case

## 0.12.22 - 2022-10-31

- refactor: require `QueryBuilderPreparer` for `DoctrineOrmEntityProvider` initialization
- refactor: remove `QueryGenerator`, `QueryBuilderPreparer` or `DoctrineOrmEntityProvider` can be used instead
- refactor: require `JoinFinder` for `QueryBuilderPreparer` initialization
- refactor: require `ConditionEvaluator` and `Sorter` for `PropertyReader` initialization
- refactor: require `TableJoiner` for `Sorter` initialization
- refactor: require `ConditionEvaluator` and  `Sorter` for `PrefilledObjectProvider` initialization
- refactor: require `TableJoiner` for `ConditionEvaluator` initialization

## 0.12.20 - 2022-10-20

- refactor: drop `TCondition` type requirement from root condition factories and introduce separate interfaces instead
- refactor: separate grouping methods into their own interface

## 0.12.19 - 2022-10-19

- feature: allow more fine-grained adjustments in `DrupalFilterValidator` subclasses
- remove unneeded factory injection in `DrupalConditionParser`
- refactor: rename `OperatorProviderInterface` and `PredefinedOperatorProvider` for clarity
- feature: validate Drupal filter for sane `path` and `memberOf` values

## 0.12.18 - 2022-10-08

- refactor: revert code back to version 0.12.16

## 0.12.17 - 2022-10-07

- feature: rename `getAccessCondition` and let it return a list

## 0.12.16 - 2022-10-06

- refactor: rename `OffsetBasedEntityProviderInterface` to `OffsetPaginatingEntityProviderInterface`
- feature: add pagination parsers
- refactor: rename pagination classes for simplicity
- refactor: remove  `illuminate/collections` dependency due to missing template type support
- refactor: rename template parameters to new naming pattern
- refactor: rework resource type property handling
- feature: add nullable support to `TypeRequirement`
- feature: improve `DynamicTransformer` exception message
- refactor: rename `SliceException` to more generic `PaginationException`

## 0.12.15 - 2022-09-27

- refactor: improve API
- refactor: improve implementation

## 0.12.14 - 2022-09-23

- refactor: adjust `split` parameters and behavior
- refactor: remove problematic static constructor method
- refactor: improve naming/type-hinting and remove assertions

## 0.12.13 - 2022-09-20

- feature: separate logic into new method to allow overriding

## 0.12.12 - 2022-09-20

- feature: allow injection of `PropertyPathProcessor` implementation

## 0.12.11 - 2022-09-20

- feature: allow validation of external read-paths
- feature: validate paths on alias processing
- feature: add basic `fields` validator
- refactor: require URL parameters in API-request handling
- feature: inject message formatting logic to allow adjustments
- feature: validate Drupal filter names

## 0.12.10 - 2022-09-13

- feature: add `CachingPropertyReader`
- refactor: improve types and type-hint usage

## 0.12.9 - 2022-09-12

- fix: avoid parameter count error in `null` check

## 0.12.8 - 2022-09-10

- fix: postpone request retrieval until needed

## 0.12.7 - 2022-09-09

- refactor: decouple `jsonapi` package from `extra` package
- refactor: prefer to handle Drupal root conditions as array

## 0.12.6 - 2022-09-08

- feature: add `WrapperObject::getPropertyValue`

## 0.12.5 - 2022-09-08

- fix: ignore unavailable source code when parsing property-read tags

## 0.12.4 - 2022-09-08

- fix: use matching parameter naming

## 0.12.3 - 2022-09-08

- fix: increase pagerfanta requirement to ^2.7

## 0.12.2 - 2022-09-08

- feature: use less strict dependency requirements
- feature: add interface method to check for type creatability

## 0.11.2 - 2022-09-07

- build composer package from `jsonapi` implementation

## 0.11.0 - 2022-09-07

- feature: add initial `jsonapi` package implementation
- fix: restore accidentally removed PHP 7.4 support

## 0.7.1 - 2022-08-31

- chore: remove temporary `phpstan-baseline.neon`, its content is covered in `phpstan.neon`

## 0.7.0 - 2022-08-31

- refactor: improve code and documentation based on tool concerns and add remaining phpstan (level 8) concerns as baseline to be worked on
- refactor: use safe functions from `thecodingmachine/safe` instead of PHP build-ins
- refactor: always return int-indexed list by `ObjectProvider`
- refactor: remove `AllTrue` PHP class, it can be constructed using `AllEqual` instead
- refactor: replace `ExtendedReflectionClass` with `nikic/php-parser` and thus fix edt-path tests
- refactor: replace trait usage in clauses with inheritance and composition
- refactor: replace trait usage in functions and clauses with inheritance and composition
- chore: remove currently unused tooling configs
- feature: disallow to-many relationships usage for sort methods in DQL
- feature: add DQL support for custom selects
- feature: add basic right join support for DQL building
- chore: improve documentation

## 0.6.4 - 2022-07-04

- fix: use correct table name after refactoring

## 0.6.3 - 2022-07-04 

- fix: set correct edt-queries version number

## 0.6.2 - 2022-07-04

- fix: handle associations when detecting DQL joins correctly
- refactor: mark setter methods in `PropertyAutoPathTrait` as internal

## 0.6.1 - 2022-06-23

- chore: Minor deployment changes related to splitting out the packages
- fix: avoid possibly unwanted `TypeRetrievalAccessException` when reading or updating a relationship

## 0.6.0 - 2022-06-20

- prepare for public release

## 0.5.5 - 2022-03-25

- Fix release tagging

## 0.5.4 - 2022-03-25

- Fix interdep version constraints

## 0.5.3 - 2022-03-25

- Fix the subtree splitting

## 0.5.2 - 2022-03-23

- Configure default branch name
- combine edt packages into monorepo with automatic subtree splitting

## v0.5.1

- Minor changes

## v0.5.0

- First tagged release
