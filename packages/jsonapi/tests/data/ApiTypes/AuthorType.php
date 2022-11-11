<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use League\Fractal\TransformerAbstract;
use Tests\data\Types\BirthType;

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
            'name' => null,
            'pseudonym' => null,
            'books' => $this->typeProvider->requestType(BookType::class)->getInstanceOrThrow(),
            'birthCountry' => null,
        ];
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }
}
