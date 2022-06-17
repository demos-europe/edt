<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Object_;
use function count;

/**
 * Provides parsing capabilities for tags, especially for such with an associated type.
 */
class DocblockTagParser
{
    /**
     * @var ExtendedReflectionClass
     */
    private $reflectionClass;
    /**
     * @var DocBlock
     */
    private $docBlock;

    /**
     * @param class-string $class
     * @throws ParseException
     */
    public function __construct(string $class)
    {
        try {
            $this->reflectionClass = new ExtendedReflectionClass($class);
            $docBlock = $this->reflectionClass->getDocComment() ?: ' ';
            $this->docBlock = DocBlockFactory::createInstance()->create($docBlock);
        } catch (Exception $e) {
            throw ParseException::docblockParsingFailed($class, $e);
        }
    }

    /**
     * @return array<int,Tag>
     */
    public function getTags(string $tagName): array
    {
        return $this->docBlock->getTagsByName($tagName);
    }

    /**
     * @param PropertyRead|PropertyWrite|Property|Param|Var_ $tag
     * @throws TagNameParseException
     */
    public function getVariableNameOfTag($tag): string
    {
        $variableName = $tag->getVariableName();
        if ('' === $variableName) {
            throw TagNameParseException::createForEmptyVariableName($tag, $this->reflectionClass->getName());
        }

        return $variableName;
    }

    /**
     * @return class-string
     *
     * @throws TagTypeParseException
     */
    public function getTagType(TagWithType $tag): string
    {
        $useStatements = $this->reflectionClass->getUseStatements();
        $namespaceName = $this->reflectionClass->getNamespaceName();

        $type = $this->getFqsenOfClass($tag, $useStatements, $namespaceName);
        if (false !== strpos($type, '|')) {
            throw TagTypeParseException::createForUnionType($tag, $type, $this->reflectionClass->getName());
        }

        return $type;
    }

    /**
     * @throws TagTypeParseException
     */
    private function getFqsenOfClass(TagWithType $tag, array $useStatements, string $namespaceName): string
    {
        $tagType = $tag->getType();
        if (!$tagType instanceof Object_) {
            // not even an object as return type
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        $typeDeclaration = (string)$tagType->getFqsen();
        $fqsenParts = explode('\\', $typeDeclaration);
        if (false === $fqsenParts || 2 > count($fqsenParts)) {
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        // look for return type in use statements
        $class = $fqsenParts[1];
        $fqsen = null;
        foreach ($useStatements as $useStatement) {
            if ($class === $useStatement['as']) {
                $fqsen = $useStatement['class'];
                break;
            }
        }

        if (isset($fqsen) && class_exists($fqsen)) {
            // usable return type found in use statements
            return $fqsen;
        }

        if (class_exists($typeDeclaration)) {
            // the declaration was a valid FQSEN in the first place
            return $typeDeclaration;
        }

        // no matching 'use' statements were found and the class is not already defined
        // with a FQSEN. Adding the current namespace as last resort to find it.
        $fqsen = $namespaceName.$typeDeclaration;
        if (class_exists($fqsen)) {
            // usable return type found in use statements
            return $fqsen;
        }

        // giving up looking for return type
        throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
    }
}
