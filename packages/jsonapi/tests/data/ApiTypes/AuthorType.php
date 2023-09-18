<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\PathAttributeReadability;
use EDT\Wrapping\PropertyBehavior\Identifier\PathIdentifierReadability;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\PathToManyRelationshipReadability;
use EDT\Wrapping\ResourceBehavior\ResourceReadability;
use League\Fractal\TransformerAbstract;

class AuthorType extends \Tests\data\Types\AuthorType implements ResourceTypeInterface
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
                    $this->typeProvider->getTypeByIdentifier(BookType::class),
                    $this->propertyAccessor
                ),
            ],
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
