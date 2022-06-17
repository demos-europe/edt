<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;

class TagTypeParseException extends ParseException
{
    /**
     * @var string
     */
    private $tagName;
    /**
     * @var string|null
     */
    private $variableName;
    /**
     * @var string
     */
    private $type;

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
    public static function createForUnionType(TagWithType $tag, string $type, string $className): self
    {
        $tagName = $tag->getName();
        $variableName = method_exists($tag, 'getVariableName')
            ? $tag->getVariableName()
            : null;
        $self = new self('Can\'t handle union types. '.self::createBaseMessage($tagName, $type, $className, $variableName));
        $self->tagName = $tagName;
        $self->variableName = $variableName;
        $self->className = $className;
        $self->type = $type;

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
