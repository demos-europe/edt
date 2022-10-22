<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\Types\AggregatedType;

class TagTypeParseException extends ParseException
{
    private string $tagName;

    private ?string $variableName = null;

    private string $type;

    /**
     * @param class-string $className
     */
    public static function createForTagType(TagWithType $tag, string $type, string $className): self
    {
        $tagName = $tag->getName();
        $variableName = method_exists($tag, 'getVariableName')
            ? $tag->getVariableName()
            : null;
        $self = new self(self::createBaseMessage($tagName, $type, $className, $variableName));
        $self->tagName = $tagName;
        $self->variableName = $variableName;
        $self->className = $className;
        $self->type = $type;

        return $self;
    }

    /**
     * @param class-string $className
     */
    public static function createForAggregatedType(TagWithType $tag, AggregatedType $type, string $className): self
    {
        $tagName = $tag->getName();
        $variableName = method_exists($tag, 'getVariableName')
            ? $tag->getVariableName()
            : null;
        $self = new self('Can\'t handle aggregated types (e.g. union types). '.self::createBaseMessage($tagName, (string) $type, $className, $variableName));
        $self->tagName = $tagName;
        $self->variableName = $variableName;
        $self->className = $className;
        $self->type = (string) $type;

        return $self;
    }

    private static function createBaseMessage(string $tagName, string $type, string $className, ?string $variableName): string
    {
        return null === $variableName
            ? "Could not find the class for tag '$tagName' with return type '$type' in class '$className'."
            : "Could not find the class for tag '$tagName' with return type '$type' and variable name '$variableName' in class '$className'.";
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }
}
