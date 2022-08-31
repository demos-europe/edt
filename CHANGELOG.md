# Changelog

## Unreleased

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
