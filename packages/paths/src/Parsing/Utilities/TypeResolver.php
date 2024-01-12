<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use Exception;
use InvalidArgumentException;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function is_string;
use function Safe\fopen;
use function Safe\fclose;
use function Safe\preg_match_all;
use function strlen;

/**
 * @internal
 */
class TypeResolver
{
    protected readonly Parser $phpParser;

    /**
     * @var array<non-empty-string, non-empty-string>
     */
    protected readonly array $useStatements;

    protected readonly \phpDocumentor\Reflection\TypeResolver $typeResolver;
    protected readonly ContextFactory $contextFactory;
    protected readonly string $sourceCode;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @throws ParseException
     */
    public function __construct(
        protected readonly ReflectionClass $reflectionClass,
    ) {
        try {
            $this->phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $fileName = $this->reflectionClass->getFileName();
            $this->sourceCode = is_string($fileName)
                ? $this->readSourceCode($fileName)
                : throw new NoSourceException("No source code was found for the file name `$fileName`.");
            $this->useStatements = $this->getUseStatements();
            $this->typeResolver = new \phpDocumentor\Reflection\TypeResolver();
            $this->contextFactory = new ContextFactory();
        } catch (NoSourceException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw ParseException::docblockParsingFailed($this->reflectionClass->getName(), $exception);
        }
    }

    /**
     * @return class-string|null
     *
     * @throws InvalidArgumentException
     * /
     */
    public function getQualifiedName(Object_ $type): ?string
    {
        $namespaceName = $this->reflectionClass->getNamespaceName();

        $typeDeclaration = (string)$type->getFqsen();
        $qualifiedNameParts = explode('\\', $typeDeclaration);
        Assert::minCount($qualifiedNameParts, 2);

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
        return null;
    }

    /**
     * @param non-empty-string $unresolvedType
     */
    public function getResolvedType(string $unresolvedType): Type
    {
        $namespace = $this->reflectionClass->getNamespaceName();
        $context = $this->contextFactory->createForNamespace($namespace, $this->sourceCode);

        return $this->typeResolver->resolve($unresolvedType, $context);
    }

    /**
     * TODO: potential replacement for {@link self::getQualifiedName}, but does not work as required yet
     *
     * @return class-string|null
     *
     * @throws TagTypeParseException
     * @throws InvalidArgumentException
     *
     * @internal
     */
    public function getQualifiedNameViaFqsenResolver(Type $tagType): ?string
    {
        $namespaceName = $this->reflectionClass->getNamespaceName();
        Assert::isInstanceOf($tagType, Object_::class);

        $tagTypeString = (string)$tagType;
        $context = $this->contextFactory->createForNamespace($namespaceName, $this->sourceCode);
        $fqsenResolver = new FqsenResolver();
        $fqsen = $fqsenResolver->resolve($tagTypeString, $context);

        $resolvedTypeString = (string)$fqsen;
        // Check if the resolved type is an object and is a class or interface
        if (class_exists($resolvedTypeString) || interface_exists($resolvedTypeString)) {
            return $resolvedTypeString;
        }

        return null;
    }

    /**
     * TODO: potential replacement for {@link self::getQualifiedName}, but does not work as required yet
     *
     * @return class-string|null
     *
     * @throws TagTypeParseException
     * @throws InvalidArgumentException
     *
     * @internal
     */
    public function getQualifiedNameViaTypeResolver(Type $tagType): ?string
    {
        $namespaceName = $this->reflectionClass->getNamespaceName();
        Assert::isInstanceOf($tagType, Object_::class);

        $tagTypeString = (string)$tagType;
        $context = $this->contextFactory->createForNamespace($namespaceName, $this->sourceCode);
        $resolvedType = $this->typeResolver->resolve($tagTypeString, $context);

        if (!$resolvedType instanceof Object_) {
            return null;
        }

        $resolvedTypeString = (string)$resolvedType;
        // Check if the resolved type is an object and is a class or interface
        if (class_exists($resolvedTypeString) || interface_exists($resolvedTypeString)) {
            return $resolvedTypeString;
        }

        return null;
    }

    /**
     * Read file source up to the line where our class is defined.
     *
     * @throws FilesystemException
     */
    protected function readSourceCode(string $fileName): string
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
     * The target of this method is to get an empty list of template parameters if the backing
     * type is of the format `SomeType` and a *non-empty* list of *non-empty* strings
     * if it is of the format `SomeType<...>`.
     *
     * As this method works with strings only (i.e. does not check if a type actually exists), it
     * will accept some invalid template parameter definitions like `SomeType<<>` (resulting in `['SomeType', ['<']]`),
     * but not `SomeType<Foo,>` (would result in `['SomeType', ['Foo', '']]` which does not matches the return type
     * and thus results in an exception instead).
     *
     * @param non-empty-string $rawTypeString
     *
     * @return array{non-empty-string, list<non-empty-string>} the part of the input string without the template parameters as first item and the list of template parameters as second item
     *
     * @throws PcreException
     * @throws InvalidArgumentException
     */
    public static function getSplitOffTemplateParameters(string $rawTypeString): array
    {
        $matches = [];

        $pattern = '/^(\\\\?(?:\w+\\\\)*\w+)(?:<(.+)>)?$/';
        $matching = preg_match_all($pattern, $rawTypeString, $matches);
        Assert::same($matching, 1, "The string `$rawTypeString` did not match the following pattern: $pattern");
        Assert::isArray($matches);

        $classNameMatches = $matches[1];
        Assert::isArray($classNameMatches);
        Assert::count($classNameMatches, 1);
        $className = $classNameMatches[0];
        Assert::stringNotEmpty($className);

        Assert::keyExists($matches, 2);
        $templatesMatches = $matches[2];
        Assert::isArray($templatesMatches);
        Assert::count($templatesMatches, 1);
        Assert::keyExists($templatesMatches, 0);
        $templateParametersString = $templatesMatches[0];
        Assert::string($templateParametersString);

        if ('' === $templateParametersString) {
            return [$className, []];
        }

        $templateParameters = self::splitTemplateParameters($templateParametersString);
        $templateParameters = array_map('trim', $templateParameters);
        Assert::allStringNotEmpty($templateParameters);

        return [$className, array_values($templateParameters)];
    }

    /**
     * @param non-empty-string $templateParameterString a value like `string,Foo<string,string>`
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException the format of the given string is invalid (i.e. missing `<` or `>`)
     */
    protected static function splitTemplateParameters(string $templateParameterString): array
    {
        $results = [];
        $depth = 0;
        $lastPosition = 0;

        // Iterate over each character in the string
        $strlen = strlen($templateParameterString);
        $i = 0;
        for (; $i < $strlen; $i++) {
            switch ($templateParameterString[$i]) {
                // Increase depth when encountering '<'
                case '<':
                    $depth++;
                    break;
                // Decrease depth when encountering '>'
                case '>':
                    $depth--;
                    Assert::greaterThanEq($depth, 0);
                    break;
                // Split on comma if depth is zero
                case ',':
                    if (0 === $depth) {
                        $results[] = substr($templateParameterString, $lastPosition, $i - $lastPosition);
                        $lastPosition = $i + 1;
                    }
                    break;
                default:
                    break;
            }
        }

        Assert::eq($depth, 0);

        // Add the last part, if any, after the final comma
        if ($lastPosition <= $i) {
            $results[] = substr($templateParameterString, $lastPosition);
        }

        return $results;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws FilesystemException
     */
    protected function getUseStatements(): array
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
        $ast = $this->phpParser->parse($this->sourceCode);
        Assert::allIsInstanceOf($ast, Node::class);
        $traverser = new NodeTraverser();
        $useCollector = new UseCollector();
        $traverser->addVisitor($useCollector);
        $traverser->traverse($ast);

        return $useCollector->getUseStatements();
    }
}
