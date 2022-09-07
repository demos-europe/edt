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
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
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
     * @var ReflectionClass
     */
    private $reflectionClass;
    /**
     * @var DocBlock|null
     */
    private $docBlock;

    /**
     * @var Parser
     */
    private $phpParser;

    /**
     * @var array<string, class-string>
     */
    private $useStatements;

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
        } catch (Exception $e) {
            throw ParseException::docblockParsingFailed($class, $e);
        }
    }

    /**
     * @param ReflectionClass|ReflectionProperty $commented must provide a `getDocComment` method
     */
    public static function createDocblock(Reflector $commented): ?DocBlock
    {
        $docBlock = $commented->getDocComment();
        if (!is_string($docBlock) || '' === $docBlock) {
            return null;
        }

        return DocBlockFactory::createInstance()->create($docBlock);
    }

    /**
     * @return array<int,Tag>
     */
    public function getTags(string $tagName): array
    {
        if (null === $this->docBlock) {
            return [];
        }

        return $this->docBlock->getTagsByName($tagName);
    }

    /**
     * @param PropertyRead|PropertyWrite|Property|Param|Var_ $tag
     * @throws TagNameParseException
     */
    public function getVariableNameOfTag($tag): string
    {
        $variableName = $tag->getVariableName();
        if (null === $variableName || '' === $variableName) {
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
        $namespaceName = $this->reflectionClass->getNamespaceName();

        $type = $this->getFqsenOfClass($tag, $namespaceName);
        if (false !== strpos($type, '|')) {
            throw TagTypeParseException::createForUnionType($tag, $type, $this->reflectionClass->getName());
        }

        return $type;
    }

    /**
     * @throws TagTypeParseException
     */
    private function getFqsenOfClass(TagWithType $tag, string $namespaceName): string
    {
        $tagType = $tag->getType();
        if (!$tagType instanceof Object_) {
            // not even an object as return type
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        $typeDeclaration = (string)$tagType->getFqsen();
        $fqsenParts = explode('\\', $typeDeclaration);
        if (2 > count($fqsenParts)) {
            throw TagTypeParseException::createForTagType($tag, (string)$tagType, $this->reflectionClass->getName());
        }

        // look for return type in use statements
        $class = $fqsenParts[1];
        $fqsen = null;
        foreach ($this->useStatements as $as => $currentUseFqsen) {
            if ($class === $as) {
                $fqsen = $currentUseFqsen;
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
                throw new \InvalidArgumentException("Failed to read source code of file: '$fileName' in line $lineNumber.");
            }
            $sourceCode .= $line;
        }

        fclose($file);

        return $sourceCode;
    }

    /**
     * @return array<string, class-string> mapping from the usable name (alias are class name) to the fully qualified class name
     *
     * @throws FilesystemException
     */
    private function getUseStatements(): array
    {
        $sourceCode = $this->readSourceCode($this->reflectionClass->getFileName());
        $ast = $this->phpParser->parse($sourceCode);
        $traverser = new NodeTraverser();
        $useCollector = new class extends NodeVisitorAbstract {
            /** @var array<string, class-string> */
            public $useStatements = [];
            public function leaveNode(Node $node) {
                if ($node instanceof Node\Stmt\Use_) {
                    foreach ($node->uses as $use) {
                        $key = null === $use->alias ? $use->name->getLast() : $use->alias->toString();
                        $this->useStatements[$key] = $use->name->toString();
                    }
                }

                return null;
            }
        };
        $traverser->addVisitor($useCollector);
        $traverser->traverse($ast);

        return $useCollector->useStatements;
    }
}
