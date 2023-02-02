<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\Types\AggregatedType;
use Throwable;

class TagTypeParseException extends ParseException
{
    /**
     * @var non-empty-string|null
     */
    private ?string $variableName = null;

    /**
     * @param class-string $className
     * @param non-empty-string $tagName
     * @param non-empty-string $type
     * @param non-empty-string $message
     */
    protected function __construct(
        string $className,
        private readonly string $tagName,
        private readonly string $type,
        string $message,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($className, $message, $code, $previous);
    }

    /**
     * @param non-empty-string $type
     * @param class-string $className
     */
    public static function createForTagType(TagWithType $tag, string $type, string $className): self
    {
        $tagName = $tag->getName();
        $variableName = self::getVariableNameOfTag($tag);
        $message = self::createBaseMessage($tagName, $type, $className, $variableName);
        $self = new self($className, $tagName, $type, $message);
        $self->variableName = $variableName;

        return $self;
    }

    /**
     * @return non-empty-string|null
     */
    protected static function getVariableNameOfTag(TagWithType $tag): ?string
    {
        return method_exists($tag, 'getVariableName')
            ? $tag->getVariableName()
            : null;
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
        $self = new self($className, $tagName, (string) $type, 'Can\'t handle aggregated types (e.g. union types). '.self::createBaseMessage($tagName, (string) $type, $className, $variableName));
        $self->variableName = $variableName;

        return $self;
    }

    /**
     * @param non-empty-string $tagName
     * @param non-empty-string $type
     * @param class-string $className
     * @param non-empty-string|null $variableName
     *
     * @return non-empty-string
     */
    private static function createBaseMessage(string $tagName, string $type, string $className, ?string $variableName): string
    {
        return null === $variableName
            ? "Could not find the class for tag '$tagName' with return type '$type' in class '$className'."
            : "Could not find the class for tag '$tagName' with return type '$type' and variable name '$variableName' in class '$className'.";
    }

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return non-empty-string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * @return non-empty-string|null
     */
    public function getVariableName(): ?string
    {
        return $this->variableName;
    }
}
