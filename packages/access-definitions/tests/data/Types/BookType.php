<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\Pagination\PagePagination;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;
use EDT\Wrapping\Utilities\EntityVerifierInterface;
use Pagerfanta\Pagerfanta;
use Tests\data\Model\Book;

/**
 * @template-implements TransferableTypeInterface<Book>
 * @template-implements IdentifiableTypeInterface<Book>
 * @template-implements FilterableTypeInterface<Book>
 * @template-implements SortableTypeInterface<Book>
 */
class BookType implements
    TransferableTypeInterface,
    FilterableTypeInterface,
    SortableTypeInterface,
    ExposableRelationshipTypeInterface
{
    private bool $exposedAsRelationship = true;

    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver,
        protected readonly EntityVerifierInterface $entityVerifier
    ) {}

    public function getReadableProperties(): array
    {
        return [
            [
                'title' => new TestAttributeReadability(['title'], $this->propertyAccessor),
                'tags' => new TestAttributeReadability(['tags'], $this->propertyAccessor),
            ], [
                'author' => new PathToOneRelationshipReadability(
                    Book::class,
                    ['author'],
                    false,
                    false,
                    $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
                    $this->propertyAccessor,
                    $this->entityVerifier
                ),
            ],
            [],
        ];
    }

    public function getFilterableProperties(): array
    {
        return [
            'title' => null,
            'author' => $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
            'tags' => null,
        ];
    }

    public function getSortableProperties(): array
    {
        return [
            'title' => null,
            'author' => $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
        ];
    }

    public function getAccessCondition(): PathsBasedInterface
    {
        return $this->conditionFactory->propertyHasNotSize(0, ['author', 'books']);
    }

    public function getEntityClass(): string
    {
        return Book::class;
    }

    public function getIdentifierFilterPath(): array
    {
        return ['title'];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function isExposedAsRelationship(): bool
    {
        return $this->exposedAsRelationship;
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }

    public function getInternalProperties(): array
    {
        return [
            'title' => null,
            'author' => $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
            'tags' => null,
        ];
    }

    public function getUpdatableProperties(): array
    {
        return [];
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
        return new PathIdReadability($this->getEntityClass(), ['id'], $this->propertyAccessor, $this->typeResolver);
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
