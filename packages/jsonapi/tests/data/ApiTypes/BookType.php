<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\Properties\JsonAttributeReadability;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;

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
                'title' => new JsonAttributeReadability(false, false, null),
                'tags' => new JsonAttributeReadability(false, false, null),
            ],
            [
                'author' => new ToOneRelationshipReadability(false, false, false, null,
                    $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
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
