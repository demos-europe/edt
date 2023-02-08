<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\AggregatedType;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionProperty;
use Reflector;
use Safe\Exceptions\FilesystemException;
use function count;
use function is_string;
use function Safe\fopen;
use function Safe\fclose;

/**
 * Provides parsing capabilities for tags, especially for such with an associated type.
 *
 * @internal
 */
class DocblockTagParser
{
    /**
     * @var ReflectionClass<object>
     */
    private ReflectionClass $reflectionClass;

    private ?DocBlock $docBlock = null;

    private Parser $phpParser;

    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $useStatements;

    /**
     * @param class-string $class
     * @throws ParseException
     */
    public function __construct(string $class)
    {
        try {
            $this->phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $this->reflectionClass = new ReflectionClass($class);
            $this->docBlock = self::createDocblock($this->reflectionClass);
            $this->useStatements = $this->getUseStatements();
        } catch (Exception $exception) {
            throw ParseException::docblockParsingFailed($class, $exception);
        }
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
            throw TagNameParseException::createForEmptyVariableName($tag->render(), $this->reflectionClass->getName());
        }

        return $variableName;
    }

    /**
     * @return class-string
     *
     * @throws TagTypeParseException
     */
    public function getPropertyType(TagWithType $tag): string
    {
        $namespaceName = $this->reflectionClass->getNamespaceName();

        return $this->getQualifiedNameOfClass($tag, $namespaceName);
    }

    /**
     * @return class-string
     *
     * @throws TagTypeParseException
     */
    private function getQualifiedNameOfClass(TagWithType $tag, string $namespaceName): string
    {
        $tagType = $tag->getType();
        if ($tagType instanceof AggregatedType) {
            throw TagTypeParseException::createForAggregatedType($tag, $tagType, $this->reflectionClass->getName());
        }
        if (!$tagType instanceof Object_) {
            // not even an object as return type
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        $typeDeclaration = (string)$tagType->getFqsen();
        $qualifiedNameParts = explode('\\', $typeDeclaration);
        if (2 > count($qualifiedNameParts)) {
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        // look for return type in use statements
        $class = $qualifiedNameParts[1];
        $qualifiedName = null;
        foreach ($this->useStatements as $as => $currentUseQualifiedName) {
            if ($class === $as) {
                $qualifiedName = $currentUseQualifiedName;
                break;
            }
        }

        if (null !== $qualifiedName && (class_exists($qualifiedName) || interface_exists($qualifiedName))) {
            // usable return type found in use statements
            return $qualifiedName;
        }

        if (class_exists($typeDeclaration) || interface_exists($typeDeclaration)) {
            // the declaration was a valid fully qualified class/interface name in the first place
            return $typeDeclaration;
        }

        // no matching 'use' statements were found and the class is not already defined
        // with a fully qualified class/interface name. Adding the current namespace as last resort to find it.
        $qualifiedName = $namespaceName.$typeDeclaration;
        if (class_exists($qualifiedName) || interface_exists($qualifiedName)) {
            // usable return type found in use statements
            return $qualifiedName;
        }

        // giving up looking for return type
        throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
    }

    /**
     * Read file source up to the line where our class is defined.
     *
     * @throws FilesystemException
     */
    private function readSourceCode(string $fileName): string
    {
        $file = fopen($fileName, 'r');
        $lineNumber = 0;
        $sourceCode = '';

        while (!feof($file)) {
            $lineNumber += 1;

            if ($lineNumber >= $this->reflectionClass->getStartLine()) {
                break;
            }

            $line = fgets($file);
            if (false === $line) {
                throw new InvalidArgumentException("Failed to read source code of file: '$fileName' in line $lineNumber.");
            }
            $sourceCode .= $line;
        }

        fclose($file);

        return $sourceCode;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws FilesystemException
     */
    private function getUseStatements(): array
    {
        $fileName = $this->reflectionClass->getFileName();
        if (!is_string($fileName)) {
            /**
             * In some cases no source code can be retrieved for the given type (e.g.
             * {@link IteratorAggregate}). For now we will assume it does not happen for with
             * "normal" types and ignore it until it becomes a problem in an actual use case.
             */
            return [];
        }
        $sourceCode = $this->readSourceCode($fileName);
        $ast = $this->phpParser->parse($sourceCode);
        $traverser = new NodeTraverser();
        $useCollector = new UseCollector();
        $traverser->addVisitor($useCollector);
        $traverser->traverse($ast);

        return $useCollector->getUseStatements();
    }
}
