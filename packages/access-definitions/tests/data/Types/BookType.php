<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\JsonApi\ApiDocumentation\AttributeTypeResolver;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\Querying\Contracts\PropertyAccessorInterface;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\Utilities\Reindexer;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\Utilities\TableJoiner;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\SortingTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\EntityDataInterface;
use EDT\Wrapping\Properties\EntityReadability;
use EDT\Wrapping\Properties\EntityUpdatability;
use Tests\data\Model\Book;
use Webmozart\Assert\Assert;

class BookType implements
    TransferableTypeInterface,
    FilteringTypeInterface,
    SortingTypeInterface,
    ExposableRelationshipTypeInterface
{
    private bool $exposedAsRelationship = true;

    /**
     * @var array<non-empty-string, Book>
     */
    private array $availableInstances;
    public function setAvailableInstances(array $instances): void
    {
        $this->availableInstances = $instances;
    }

    public function __construct(
        protected readonly PathsBasedConditionFactoryInterface $conditionFactory,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly AttributeTypeResolver $typeResolver,
    ) {}

    public function getReadableProperties(): EntityReadability
    {
        return new EntityReadability(
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

    public function getAccessConditions(): array
    {
        //return [$this->conditionFactory->propertyHasNotSize(0, ['author', 'books'])];
        return [];
    }

    public function getEntityClass(): string
    {
        return Book::class;
    }

    public function isExposedAsRelationship(): bool
    {
        return $this->exposedAsRelationship;
    }

    public function getUpdatability(): EntityUpdatability
    {
        return new EntityUpdatability([], [], []);
    }

    public function getTypeName(): string
    {
        return self::class;
    }

    public function getEntitiesForRelationship(array $identifiers, array $conditions, array $sortMethods): array
    {
        Assert::isEmpty($conditions);
        Assert::isEmpty($sortMethods);
        $books = [];
        foreach ($identifiers as $identifier) {
            foreach ($this->availableInstances as $instance) {
                if ($identifier === $instance->getId()) {
                    $books[] = $instance;
                }
            }
        }

        return $books;
    }

    public function getExpectedUpdateProperties(): ExpectedPropertyCollection
    {
        throw new \RuntimeException();
    }

    public function updateEntity(string $entityId, EntityDataInterface $entityData): ?object
    {
        throw new \RuntimeException();
    }

    public function assertMatchingEntities(array $entities, array $conditions): void
    {
        $this->getReindexer()->assertMatchingEntities($entities, $conditions);
    }

    public function assertMatchingEntity(object $entity, array $conditions): void
    {
        $this->getReindexer()->assertMatchingEntity($entity, $conditions);
    }

    public function isMatchingEntity(object $entity, array $conditions): bool
    {
        throw new \RuntimeException();
    }

    public function reindexEntities(array $entities, array $conditions, array $sortMethods): array
    {
        return $this->getReindexer()->reindexEntities($entities, $conditions, $sortMethods);
    }

    protected function getReindexer(): Reindexer
    {
        $tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $conditionEvaluator = new ConditionEvaluator($tableJoiner);
        $sorter = new Sorter($tableJoiner);

        return new Reindexer($conditionEvaluator, $sorter);
    }

    public function getEntityForRelationship(string $identifier, array $conditions): object
    {
        throw new \RuntimeException();
    }
}
