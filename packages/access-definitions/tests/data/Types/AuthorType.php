<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipReadability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipUpdatability;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;
use Pagerfanta\Pagerfanta;
use Tests\data\Model\Person;

/**
 * @template-implements TransferableTypeInterface<Person>
 * @template-implements IdentifiableTypeInterface<Person>
 * @template-implements FilterableTypeInterface<Person>
 * @template-implements SortableTypeInterface<Person>
 */
class AuthorType implements
    TransferableTypeInterface,
    FilterableTypeInterface,
    SortableTypeInterface,
    ExposableRelationshipTypeInterface,
    AliasableTypeInterface
{
    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly EntityVerifierInterface $entityVerifier,
        protected readonly AttributeTypeResolver $typeResolver
    ) {}

    public function getReadableProperties(): array
    {
        return [
            [
                'name' => new TestAttributeReadability(['name'], $this->propertyAccessor),
                'pseudonym' => new TestAttributeReadability(['pseudonym'], $this->propertyAccessor),
                'birthCountry' => new TestAttributeReadability(['birth', 'country'], $this->propertyAccessor),
            ],
            [],
            [
                'books' => new PathToManyRelationshipReadability(
                    $this->getEntityClass(),
                    ['books'],
                    false,
                    false,
                    $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
                    $this->propertyAccessor,
                    $this->entityVerifier
                ),
            ],
        ];
    }

    public function getFilterableProperties(): array
    {
        return [
            'name' => null,
            'pseudonym' => null,
            'books' => $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
            'birthCountry' => null,
        ];
    }

    public function getSortableProperties(): array
    {
        return [
            'name' => null,
            'pseudonym' => null,
            'birthCountry' => null,
        ];
    }

    public function getAccessCondition(): PathsBasedInterface
    {
        return $this->conditionFactory->allConditionsApply(
            $this->conditionFactory->propertyHasNotSize(0, ['books']),
            $this->conditionFactory->propertyHasNotSize(0, ['writtenBooks'])
        );
    }

    public function getEntityClass(): string
    {
        return Person::class;
    }

    public function getIdentifierFilterPath(): array
    {
        return ['name'];
    }

    public function getAliases(): array
    {
        return [
            'birthCountry' => ['birth', 'country'],
            'writtenBooks' => ['books'],
        ];
    }

    public function isExposedAsRelationship(): bool
    {
        return true;
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }

    public function getUpdatableProperties(): array
    {
        $bookType = $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow();

        return [
            [
                'name' => new TestAttributeUpdatability(['name'], $this->propertyAccessor),
                'birthCountry' => new TestAttributeUpdatability(['birth', 'country'], $this->propertyAccessor),
            ],
            [],
            [
                'books' => new PathToManyRelationshipUpdatability(
                    self::class,
                    [],
                    [
                        $bookType->getAccessCondition()
                    ],
                    $bookType,
                    ['books'],
                    $this->propertyAccessor,
                    $this->entityVerifier
                ),
            ],
        ];
    }

    public function getInternalProperties(): array
    {
        return [
            'books' => $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
            'writtenBooks' => $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
            'name' => null,
            'birthCountry' => null,
            'pseudonym' => null,
        ];
    }

    public function getIdentifier(): string
    {
        return self::class;
    }

    public function getIdentifierSortingPath(): array
    {
        throw new \Exception('Not implemented');
    }

    public function getIdentifierReadability(): IdReadabilityInterface
    {
        return new class implements IdReadabilityInterface {
            public function getValue(object $entity): int|string
            {
                return 0;
            }

            public function isDefaultField(): bool
            {
                return false;
            }

            public function getPropertySchema(): array
            {
                return [];
            }
        };
    }

    public function fetchPagePaginatedEntities(
        array $conditions,
        array $sortMethods,
        PagePagination $pagination
    ): Pagerfanta {
        throw new \Exception('Not implemented');
    }

    public function fetchEntities(array $conditions, array $sortMethods): array
    {
        throw new \Exception('Not implemented');
    }

    public function fetchEntity(int|string $entityIdentifier, array $conditions): ?object
    {
        throw new \Exception('Not implemented');
    }
}
