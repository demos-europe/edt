# Changelog

## Unreleased

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
