<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use InvalidArgumentException;
use OutOfBoundsException;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionClass;
use ReflectionProperty;
use Webmozart\Assert\Assert;

class ClassOrInterfaceType implements TypeInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $shortClassName;

    /**
     * @param class-string $fullyQualifiedName
     * @param list<TypeInterface> $templateParameters
     */
    protected function __construct(
        protected readonly string $fullyQualifiedName,
        protected readonly array $templateParameters
    ) {
        $pathNames = explode('\\', $fullyQualifiedName);
        $shortClassName = array_pop($pathNames);
        Assert::stringNotEmpty($shortClassName);
        $this->shortClassName = $shortClassName;
    }

    /**
     * FIXME: phpDocumentor currently does not support proper generics. Classes/interfaces with
     * template parameters will result in a {@link Collection} instance. That class however silently
     * omits all template parameters except the last two, and thus is not reliable to use.
     *
     * @param TypeResolver $typeResolver
     *
     * @see https://github.com/phpDocumentor/phpDocumentor/issues/2122
     */
    public static function fromType(Type $type, TypeResolver $typeResolver): self
    {
        if ($type instanceof Object_) {
            return self::fromObjectType($type, [], $typeResolver);
        }

        if ($type instanceof Collection) {
            $mainTypeName = (string)$type->getFqsen();
            Assert::stringNotEmpty($mainTypeName);
            $resolvedType = $typeResolver->getResolvedType($mainTypeName);

            return self::fromType($resolvedType, $typeResolver);
        }

        throw new InvalidArgumentException("Failed to resolve class or interface from given type: {$type->__toString()}. Currently only `" . Object_::class . '` and (with limitations) `'. Collection::class . '` are supported.');
    }

    /**
     * @param class-string $class
     * @param list<TypeInterface> $templateParameters
     */
    public static function fromFqcn(string $class, array $templateParameters = []): self
    {
        return new self($class, $templateParameters);
    }

    /**
     * @param list<TypeInterface> $templateParameters
     */
    protected static function fromObjectType(Object_ $type, array $templateParameters, TypeResolver $typeResolver): self
    {
        $rawString = (string)$type;
        $fullyQualifiedName = $typeResolver->getQualifiedName($type);
        Assert::notNull($fullyQualifiedName, "Failed to find fully qualified name for the following string: $rawString");

        return self::fromFqcn($fullyQualifiedName, $templateParameters);
    }

    /**
     * Throws an exception if the backing type of this instance does not allow for a single FQCN.
     *
     * @return class-string the fully qualified class name
     */
    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    /**
     * Get the template parameters of this type.
     *
     * @return list<TypeInterface> empty if the type was not templated (`SomeType`)
     */
    public function getTemplateParameters(): array
    {
        return $this->templateParameters;
    }

    /**
     * Expects that the given index denotes a template parameter.
     *
     * @param int $index starts with 0, negative values will denote the template parameters in reverse (i.e. from the end to the front); overflows (in either direction) are not allowed
     *
     * @throws OutOfBoundsException if the given index does not denote any existing template parameter
     */
    public function getTemplateParameter(int $index): TypeInterface
    {
        if ($index < 0) {
            Assert::count($this->templateParameters, -$index);
            $index = count($this->templateParameters) + $index;
        } else {
            Assert::count($this->templateParameters, $index + 1);
        }

        return $this->templateParameters[$index];
    }

    /**
     * @return non-empty-string
     */
    public function getShortClassName(): string
    {
        return $this->shortClassName;
    }

    public function getAllFullyQualifiedNames(): array
    {
        $nestedFqcns = array_map(
            static fn (TypeInterface $parameter): array => $parameter->getAllFullyQualifiedNames(),
            $this->templateParameters
        );
        $fqcns = array_merge(...$nestedFqcns);
        $fqcns[] = $this->fullyQualifiedName;

        return $fqcns;
    }

    public function getFullString(bool $withSimpleClassNames): string
    {
        $className = $withSimpleClassNames
            ? $this->shortClassName
            : $this->fullyQualifiedName;

        if ([] === $this->templateParameters) {
            return $className;
        }

        $templateParameterStrings = array_map(
            static fn (TypeInterface $parameter): string => $parameter->getFullString($withSimpleClassNames),
            $this->templateParameters
        );

        return "$className<" . implode(',', $templateParameterStrings) . '>';
    }
}
