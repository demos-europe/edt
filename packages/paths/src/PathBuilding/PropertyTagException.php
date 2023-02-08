<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use phpDocumentor\Reflection\DocBlock\Tag;
use RuntimeException;

class PropertyTagException extends RuntimeException
{
    public static function invalidType(PropertyTag $propertyTag, Tag $tag): self
    {
        $expectedClass = $propertyTag->getCorrespondingType();
        $actualClass = $tag::class;

        throw new self("Searched for $propertyTag->value tags and expected '$expectedClass' instances. Found {$tag->getName()} tag with '$actualClass' instead.");
    }
}
