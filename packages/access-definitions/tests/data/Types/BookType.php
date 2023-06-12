<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\PhpReindexableType;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\ReadabilityCollection;
use EDT\Wrapping\Properties\UpdatablePropertyCollection;
use EDT\Wrapping\Utilities\PropertyPathProcessorFactory;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Tests\data\Model\Book;

class BookType implements
    TransferableTypeInterface,
    FilteringTypeInterface,
    SortingTypeInterface,
    ExposableRelationshipTypeInterface
{
    private bool $exposedAsRelationship = true;

    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver,
    ) {}

    public function getReadableProperties(): ReadabilityCollection
    {
        return new ReadabilityCollection(
            [
                'title' => new TestAttributeReadability(['title'], $this->propertyAccessor),
                'tags' => new TestAttributeReadability(['tags'], $this->propertyAccessor),
            ], [
                'author' => new PathToOneRelationshipReadability(
                    Book::class,
                    ['author'],
                    false,
                    false,
                    $this->typeProvider->getTypeByIdentifier(AuthorType::class),
                    $this->propertyAccessor
                ),
            ],
            [],
            new PathIdReadability(
                $this->getEntityClass(),
                ['id'],
                $this->propertyAccessor,
                $this->typeResolver
            )
        );
    }

    public function getFilteringProperties(): array
    {
        return [
            'title' => new PropertyLink(['title'], null),
            'author' => new PropertyLink(
                ['author'],
                $this->typeProvider->getTypeByIdentifier(AuthorType::class)
            ),
            'tags' => new PropertyLink(['tags'], null),
        ];
    }

    public function getSortingProperties(): array
    {
        return [
            'title' => new PropertyLink(['title'], null),
            'author' => new PropertyLink(
                ['author'],
                $this->typeProvider->getTypeByIdentifier(AuthorType::class)
            ),
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

    public function isExposedAsRelationship(): bool
    {
        return $this->exposedAsRelationship;
    }

    public function getDefaultSortMethods(): array
    {
        return [];
    }

    public function getUpdatableProperties(): UpdatablePropertyCollection
    {
        return new UpdatablePropertyCollection([], [], []);
    }

    public function getTypeName(): string
    {
        return self::class;
    }

    public function getEntityByIdentifier(int|string $identifier, array $conditions): object
    {
        throw new \RuntimeException();
    }

    public function getEntitiesByIdentifiers(array $identifiers, array $conditions, array $sortMethods): array
    {
        throw new \RuntimeException();
    }

    public function getExpectedUpdateProperties(): ExpectedPropertyCollection
    {
        throw new \RuntimeException();
    }

    public function updateEntity(UpdateRequestBody $requestBody): ?object
    {
        throw new \RuntimeException();
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $reindexer = new PhpReindexableType(
            $this,
            new SchemaPathProcessor(new PropertyPathProcessorFactory()),
            new ConditionEvaluator($tableJoiner),
            new Sorter($tableJoiner)
        );

        $reindexer->assertMatchingEntities($entities, $conditions);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $reindexer = new PhpReindexableType(
            $this,
            new SchemaPathProcessor(new PropertyPathProcessorFactory()),
            new ConditionEvaluator($tableJoiner),
            new Sorter($tableJoiner)
        );

        $reindexer->assertMatchingEntity($entity, $conditions);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        throw new \RuntimeException();
    }

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        $tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $reindexer = new PhpReindexableType(
            $this,
            new SchemaPathProcessor(new PropertyPathProcessorFactory()),
            new ConditionEvaluator($tableJoiner),
            new Sorter($tableJoiner)
        );

        return $reindexer->reindexEntities($entities, $conditions, $sortMethods);
    }
}
