<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;

class TagNameParseException extends ParseException
{
    private string $property;

    /**
     * @param class-string $className
     */
    public static function createForEmptyVariableName(TagWithType $property, string $className): self
    {
        $renderedProperty = $property->render();
        $self = new self("Empty property name parsed in $className from @property-read: '$renderedProperty', please check if you used a '$' directly in front of the property name, otherwise what you intended to set as property name might has been interpreted as description.");
        $self->className = $className;
        $self->property = $renderedProperty;

        return $self;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
