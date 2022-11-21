<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use League\Fractal\TransformerAbstract;

class AuthorType extends \Tests\data\Types\AuthorType implements ResourceTypeInterface
{
    public static function getName(): string
    {
        return self::class;
    }

    public function getTransformer(): TransformerAbstract
    {
        return new class() extends TransformerAbstract {};
    }

    /**
     * Overwrites its parent relationships with reference to resource type implementations.
     */
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
                'books' => new ToManyRelationshipReadability(false, false, false, null,
                    $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow()
                ),
            ]
        ];
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }

    public function getReadableResourceTypeProperties(): array
    {
        return $this->getReadableProperties();
    }
}
