<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function Safe\preg_match;

class PropertyType
{
    /**
     * @var list<non-empty-string>|null
     */
    protected ?array $templateParameters = null;
    /**
     * @var class-string|null
     */
    protected ?string $fqcn = null;

    public function __construct(
        protected readonly Type $type,
        protected readonly DocblockTagParser $parser
    ) {}

    /**
     * Throws an exception if the backing type of this instance does not allow for a single FQCN.
     *
     * @return class-string the fully qualified class name
     */
    public function getFqcn(): string
    {
        if (null === $this->fqcn) {
            Assert::isInstanceOf($this->type, Object_::class);
            $this->fqcn = $this->parser->getQualifiedName($this->type);
            Assert::notNull($this->fqcn);
        }

        return $this->fqcn;
    }

    /**
     * Get the template parameters of the backing type.
     *
     * Requires the backing type to be an {@link Object_} (i.e. a single class, not a non-class
     * or aggregated types).
     *
     * Very lenient regarding the content of the list otherwise. The target is to simply get an empty list
     * if the backing type is of the format `SomeType` and a *non-empty* list of *non-empty* strings
     * if it is of the format `SomeType<...>`. Hence, will accept some invalid template
     * parameter definitions like `SomeType<<>` (resulting in `['>']`) but not `SomeType<Foo,>`
     * (would result in `['Foo', '']`).
     *
     * @return list<non-empty-string> empty if the type was not templated (`SomeType`)
     */
    public function getTemplateParameters(): array
    {
        if (null === $this->templateParameters) {
            $type = $this->type;
            Assert::isInstanceOf($type, Object_::class);
            $typeString = (string) $type;
            Assert::stringNotEmpty($typeString);

            $this->templateParameters = self::getTemplateParameterStrings($typeString);
        }

        return $this->templateParameters;
    }

    /**
     * @param non-empty-string $typeString
     *
     * @return list<non-empty-string>
     */
    // TODO: make non-static/non-public but keep testing it
    public static function getTemplateParameterStrings(string $typeString): array
    {
        $pattern = '/^\w+(?:<(.*)>)?$/';
        $matches = [];
        $matching = preg_match($pattern, $typeString, $matches);

        Assert::same($matching, 1);
        Assert::isArray($matches);
        if (!array_key_exists(1, $matches)) {
            return [];
        }

        $templateParametersString = $matches[1];
        Assert::string($templateParametersString);
        $result = array_map(
            static fn (string $part): string => trim($part),
            explode(',', $templateParametersString)
        );
        Assert::allStringNotEmpty($result);

        return $result;
    }

    /**
     * Expects that the backing type of this instance is a class/interface with template parameters
     * and that the given index denotes a template parameter that can be expressed by a FQCN.
     *
     * @param int $param starts with 0, negative values will denote the template parameters in reverse (i.e. from the end to the front); overflows (in either direction) are not allowed
     *
     * @return class-string
     */
    public function getTemplateParameterFqcn(int $param): string
    {
        $templateParameters = $this->getTemplateParameters();
        if ($param < 0) {
            Assert::count($templateParameters, -$param);
            $templateParameter = $templateParameters[count($templateParameters)+$param];
        } else {
            Assert::count($templateParameters, $param + 1);
            $templateParameter = $templateParameters[$param];
        }

        $resolvedType = $this->parser->getResolvedType($templateParameter);
        Assert::isInstanceOf($resolvedType, Object_::class);

        $type = $this->parser->getQualifiedName($resolvedType);
        Assert::notNull($type);

        return $type;
    }
}
