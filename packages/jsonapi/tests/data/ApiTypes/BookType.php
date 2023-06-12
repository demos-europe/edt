<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\Properties\Attributes\PathAttributeReadability;
use EDT\JsonApi\Properties\Id\PathIdReadability;
use EDT\JsonApi\Properties\Relationships\PathToOneRelationshipReadability;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Properties\ReadabilityCollection;
use League\Fractal\TransformerAbstract;

class BookType extends \Tests\data\Types\BookType implements ResourceTypeInterface
{
    public function getTypeName(): string
    {
        return self::class;
    }

    /**
     * Overwrites its parent relationships with reference to resource type implementations.
     */
    public function getReadableProperties(): ReadabilityCollection
    {
        return new ReadabilityCollection(
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

    public function getTransformer(): TransformerAbstract
    {
    }
}
