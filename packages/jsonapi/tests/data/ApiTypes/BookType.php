<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ApiDocumentation\DefaultField;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\PathToOneRelationshipReadability;
use EDT\Wrapping\ResourceBehavior\ResourceReadability;
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
    public function getReadability(): ResourceReadability
    {
        return new ResourceReadability(
            [
                'title' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['title'],
                    DefaultField::NO,
                    $this->propertyAccessor,
                    $this->typeResolver
                ),
                'tags' => new PathAttributeReadability(
                    $this->getEntityClass(),
                    ['tags'],
                    DefaultField::NO,
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
            new PathIdentifierReadability(
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
