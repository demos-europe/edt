<?php

declare(strict_types=1);

namespace Tests\data\ApiTypes;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
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

    public function getReadableProperties(): array
    {
        $properties = parent::getReadableProperties();
        // overwrite relationships with reference to resource type implementation
        $properties['books'] = BookType::class;
        return $properties;
    }

    public function isExposedAsPrimaryResource(): bool
    {
        return true;
    }
}
