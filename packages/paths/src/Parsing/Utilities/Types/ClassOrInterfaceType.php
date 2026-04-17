<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities\Types;

use EDT\Parsing\Utilities\TypeResolver;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
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
     * @see https://github.com/phpDocumentor/phpDocumentor/issues/2122
     */
    public static function fromType(Object_|Collection $type, TypeResolver $typeResolver): self
    {
        $typeString = (string) $type;
        Assert::stringNotEmpty($typeString);
        [$actualClassName, $actualTemplateParameters] = $typeResolver::getSplitOffTemplateParameters($typeString);
        $actualTemplateParameters = array_map(
            static fn(string $templateParameter): TypeInterface => new LazyType($templateParameter, $typeResolver),
            $actualTemplateParameters
        );

        if ($type instanceof Collection) {
            $mainTypeName = (string)$type->getFqsen();
            Assert::stringNotEmpty($mainTypeName);
            $type = $typeResolver->getResolvedType($mainTypeName);
        }

        Assert::isInstanceOf(
            $type,
            Object_::class,
            "Failed to resolve class or interface from given type: {$type->__toString()}. Currently only `"
            . Object_::class
            . '` and (with limitations) `'
            . Collection::class
            . '` are supported.'
        );

        return self::fromObjectType($type, $actualTemplateParameters, $typeResolver);
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
    public static function fromObjectType(Object_ $type, array $templateParameters, TypeResolver $typeResolver): self
    {
        $rawString = (string)$type;
        $fullyQualifiedName = $typeResolver->getQualifiedName($type);
        Assert::notNull($fullyQualifiedName, "Failed to find fully qualified name for the following string: $rawString");

        return self::fromFqcn($fullyQualifiedName, $templateParameters);
    }

    /**
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

    public function getTemplateParameter(int $index): TypeInterface
    {
        $message = "Expected type `$this->fullyQualifiedName` to contain at least %d template parameters. Got: %d.";
        if ($index < 0) {
            Assert::greaterThanEq($this->templateParameters, -$index, $message);
            $index = count($this->templateParameters) + $index;
        } else {
            Assert::count($this->templateParameters, $index + 1, $message);
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
