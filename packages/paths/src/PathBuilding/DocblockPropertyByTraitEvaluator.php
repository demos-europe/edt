<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Parsing\Utilities\DocblockTagResolver;
use EDT\Parsing\Utilities\NoSourceException;
use EDT\Parsing\Utilities\ParseException;
use EDT\Parsing\Utilities\TypeResolver;
use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
use EDT\Parsing\Utilities\Types\NonClassOrInterfaceType;
use EDT\Parsing\Utilities\Types\TypeInterface;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionClass;
use ReflectionException;
use Webmozart\Assert\Assert;
use function array_key_exists;

class DocblockPropertyByTraitEvaluator
{
    /**
     * Cache of already parsed classes
     *
     * @var array<class-string, array<non-empty-string, TypeInterface>>
     */
    private array $parsedClasses = [];

    /**
     * @param list<trait-string> $targetTraits set to empty list to not filter by traits
     * @param non-empty-list<PropertyTag> $targetTags The docblock tags to look for when parsing the docblock. Defaults to (effectively) &#64;property-read.
     */
    public function __construct(
        protected readonly TraitEvaluator $traitEvaluator,
        protected readonly array $targetTraits,
        protected readonly array $targetTags
    ) {}

    /**
     * @param class-string $class
     *
     * @return array<non-empty-string, TypeInterface>
     *
     * @throws ParseException
     */
    public function parseProperties(string $class, bool $includeParents = false): array
    {
        if ($includeParents) {
            $classes = $this->traitEvaluator->getAllParents($class);
            array_unshift($classes, $class);
        } else {
            $classes = [$class];
        }

        return $this->parsePropertiesOfClasses($classes);
    }

    /**
     * @return non-empty-list<PropertyTag>
     */
    public function getTargetTags(): array
    {
        return $this->targetTags;
    }

    /**
     * Searches the class-docblock of the given class for the kind of tags that were set
     * in {@link self::$targetTags} and which type uses all the traits set in {@link self::$targetTraits}.
     *
     * Docblock tags that fail to do either will be ignored. Tags that do both will be returned.
     *
     * The result for the given class will be cached in this instance and directly
     * returned on consecutive calls, without repeated docblock processing.
     *
     * @param class-string $class
     *
     * @return array<non-empty-string, TypeInterface> mapping from the property name to its type
     *
     * @throws ParseException
     */
    protected function getClassOrInterfacePropertiesOfClass(string $class): array
    {
        if (!array_key_exists($class, $this->parsedClasses)) {
            $this->parsedClasses[$class] = $this->parseClassOrInterfacePropertiesOfClass($class);
        }

        return $this->parsedClasses[$class];
    }

    /**
     * @param class-string $class
     * @return array<non-empty-string, TypeInterface>
     *
     * @throws ParseException
     * @throws ReflectionException
     */
    protected function parseClassOrInterfacePropertiesOfClass(string $class): array
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            $typeResolver = new TypeResolver($reflectionClass);
            $docblockTagResolver = new DocblockTagResolver($reflectionClass);
            $nestedProperties = array_map(function (PropertyTag $targetTag) use ($typeResolver, $docblockTagResolver): array {
                $propertyTags = $docblockTagResolver->getTags($targetTag->value);
                $propertyTags = array_map([$targetTag, 'convertToCorrespondingType'], $propertyTags);
                $propertyNames = array_map([$docblockTagResolver, 'getVariableNameOfTag'], $propertyTags);
                $propertyTags = array_combine($propertyNames, $propertyTags);
                $propertyTypes = array_map(
                    static function(TagWithType $tag) use ($typeResolver): TypeInterface {
                        $tagType = $tag->getType();

                        if ($tagType instanceof Object_ || $tagType instanceof Collection) {
                            return ClassOrInterfaceType::fromType($tagType, $typeResolver);
                        }

                        $tagTypeString = (string) $tagType;
                        Assert::stringNotEmpty($tagTypeString);

                        return NonClassOrInterfaceType::fromRawString($tagTypeString);
                    },
                    $propertyTags
                );

                return array_filter($propertyTypes, [$this, 'isUsingRequiredTraits']);
            }, $this->targetTags);

            return array_merge(...$nestedProperties);
        } catch (NoSourceException $exception) {
            return [];
        }
    }

    /**
     * Parses the property tags of all given classes and returns the result as one flat
     * array. Tags with the same property name will override each other, with the class
     * being passed later in the parameters taking precedence.
     *
     * @param non-empty-list<class-string> $classes
     *
     * @return array<non-empty-string, TypeInterface>
     *
     * @throws ParseException
     */
    protected function parsePropertiesOfClasses(array $classes): array
    {
        $nestedPropertiesByClass = array_map([$this, 'getClassOrInterfacePropertiesOfClass'], $classes);

        return array_merge(...array_reverse($nestedPropertiesByClass));
    }

    /**
     * Accessed property types must use all traits in {@link self::$targetTraits}.
     *
     * Any primitive type will be filtered out, if {@link self::$targetTraits} is not empty.
     */
    protected function isUsingRequiredTraits(TypeInterface $propertyType): bool
    {
        if (0 === count($this->targetTraits)) {
            return true;
        }

        $fullyQualifiedName = $propertyType->getFullyQualifiedName();
        if (null === $fullyQualifiedName) {
            // filter out all non-classes/non-interfaces if any traits are required
            return false;
        }
        return $this->traitEvaluator->isClassUsingAllTraits($fullyQualifiedName, $this->targetTraits);
    }
}
