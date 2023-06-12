<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Relationships\PathToManyRelationshipReadability;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;

class AuthorType extends \Tests\data\Types\AuthorType implements ResourceTypeInterface
{
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * Overwrites its parent relationships with reference to resource type implementations.
     */
    public function getReadableProperties(): array
    {
        return [
            [
                'name' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['name'],
                    false,
                    $this->propertyAccessor,
                    $this->typeResolver
                ),
                'pseudonym' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['pseudonym'],
                    false,
                    $this->propertyAccessor,
                    $this->typeResolver,
                ),
                'birthCountry' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['birth', 'country'],
                    false,
                    $this->propertyAccessor,
                    $this->typeResolver
                ),
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
            ]
        ];
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }
}
