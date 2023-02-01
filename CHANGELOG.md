# Changelog

## Unreleased

* fix: update `simplify/monorepo-builder` dependency to solve monorepo-split bug
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
- refactor: remove `TypeInterface::isAvailable` requirement from type wrapper factories (`WrapperObjectFactory`/`WrapperArrayFactory`), calls must decide by themselves on the restriction of the root type (e.g. `ExposablePrimaryResourceTypeInterface::isExposedAsPrimaryResource`), relationships will automatically checked for `true` return in `ExposableRelationshipTypeInterface::isExposedAsRelationship`
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
