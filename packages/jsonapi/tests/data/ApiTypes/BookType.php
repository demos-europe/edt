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

    public function getReadableProperties(): array
    {
        $properties = parent::getReadableProperties();
        // overwrite relationships with reference to resource type implementation
        $properties['author'] = AuthorType::class;
        return $properties;
    }
}
