<?php

declare(strict_types=1);

namespace Tests\data\Types;

use EDT\ConditionFactory\PathsBasedConditionFactoryInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\RelationshipUpdatability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\Updatability;
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
    IdentifiableTypeInterface,
    ExposableRelationshipTypeInterface,
    AliasableTypeInterface
{
    private PathsBasedConditionFactoryInterface $conditionFactory;

    protected TypeProviderInterface $typeProvider;

    public function __construct(
        PathsBasedConditionFactoryInterface $conditionFactory,
        TypeProviderInterface $typeProvider
    ) {
        $this->conditionFactory = $conditionFactory;
        $this->typeProvider = $typeProvider;
    }

    public function getReadableProperties(): array
    {
        return [
            [
                'name' => new AttributeReadability(false, false, null),
                'pseudonym' => new AttributeReadability(false, false, null),
                'birthCountry' => new AttributeReadability(false, false, null),
            ],
            [],
            [
                'books' => new ToManyRelationshipReadability(
                    false,
                    false,
                    false,
                    null,
                    $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
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

    public function getIdentifierPropertyPath(): array
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
                'name' => new Updatability([], []),
                'birthCountry' => new Updatability([], []),
            ],
            [],
            [
                'books' => new RelationshipUpdatability([], [
                    $bookType->getAccessCondition()
                ], $bookType->getEntityClass()),
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
}
