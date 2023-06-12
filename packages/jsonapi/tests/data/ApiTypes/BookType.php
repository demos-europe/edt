<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;

class BookType extends \Tests\data\Types\BookType implements ResourceTypeInterface
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
                'title' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['title'],
                    false,
                    $this->propertyAccessor,
                    $this->typeResolver
                ),
                'tags' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['tags'],
                    false,
                    $this->propertyAccessor,
                    $this->typeResolver,
                ),
            ],
            [
                'author' => new PathToOneRelationshipReadability(
                    $this->getEntityClass(),
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

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }
}
