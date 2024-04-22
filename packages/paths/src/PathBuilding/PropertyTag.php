<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

enum PropertyTag: string
{
    case VAR = 'var';
    case PROPERTY = 'property';
    case PROPERTY_READ = 'property-read';

    /**
     * Usage is discouraged, as the use case of this enum is getting property values, not setting them.
     */
    case PROPERTY_WRITE = 'property-write';
    case PARAM = 'param';

    public function convertToCorrespondingType(Tag $tag): PropertyRead|PropertyWrite|Property|Param|Var_
    {
        $correspondingType = $this->getCorrespondingType();
        if ($tag instanceof $correspondingType) {
            return $tag;
        }

        throw PropertyTagException::invalidType($this, $tag);
    }

    /**
     * @return class-string<PropertyRead|PropertyWrite|Property|Param|Var_>
     */
    public function getCorrespondingType(): string
    {
        return match ($this) {
            self::PROPERTY_READ => PropertyRead::class,
            self::PROPERTY_WRITE => PropertyWrite::class,
            self::PROPERTY => Property::class,
            self::PARAM => Param::class,
            self::VAR => Var_::class,
        };
    }
}
