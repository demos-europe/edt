<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionProperty;
use function is_string;

/**
 * Provides parsing capabilities for tags, especially for such with an associated type.
 *
 * @internal
 */
class DocblockTagResolver
{
    protected readonly ?DocBlock $docBlock;

    /**
     * @param ReflectionClass<object> $reflectionClass
     */
    public function __construct(
        protected readonly ReflectionClass $reflectionClass
    ) {
        $this->docBlock = self::createDocblock($this->reflectionClass);
    }

    /**
     * @param ReflectionClass<object>|ReflectionProperty $commented
     */
    public static function createDocblock(ReflectionClass|ReflectionProperty $commented): ?DocBlock
    {
        $docBlock = $commented->getDocComment();
        if (!is_string($docBlock) || '' === $docBlock) {
            return null;
        }

        // TODO: set context and location?
        return DocBlockFactory::createInstance()->create($docBlock);
    }

    /**
     * @param non-empty-string $tagName
     *
     * @return list<Tag>
     */
    public function getTags(string $tagName): array
    {
        if (null === $this->docBlock) {
            return [];
        }

        return array_values($this->docBlock->getTagsByName($tagName));
    }

    /**
     * @return non-empty-string
     *
     * @throws TagNameParseException
     */
    public function getVariableNameOfTag(PropertyRead|PropertyWrite|Property|Param|Var_ $tag): string
    {
        $variableName = $tag->getVariableName();
        if (null === $variableName || '' === $variableName) {
            $renderedProperty = $tag->render();
            throw TagNameParseException::createForEmptyVariableName($renderedProperty, $this->reflectionClass->getName());
        }

        return $variableName;
    }
}
