<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use League\Fractal\TransformerAbstract;

class BookType extends \Tests\data\Types\BookType implements ResourceTypeInterface
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
            'title' => null,
            'author' => $this->typeProvider->requestType(AuthorType::class)->getInstanceOrThrow(),
            'tags' => null,
        ];
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }
}
