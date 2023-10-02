<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Parsing\Utilities\DocblockTagResolver;
use EDT\Parsing\Utilities\TypeInterface;
use EDT\Parsing\Utilities\TypeResolver;
use EDT\Parsing\Utilities\NoSourceException;
use EDT\Parsing\Utilities\ParseException;
use EDT\Parsing\Utilities\ClassOrInterfaceType;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use ReflectionClass;
use Webmozart\Assert\Assert;
use function array_key_exists;

class DocblockPropertyByTraitEvaluator
{
    /**
     * Cache of already parsed classes
     *
     * @var array<class-string, array<non-empty-string, ClassOrInterfaceType>>
     */
    private array $parsedClasses = [];

    /**
     * @param list<non-empty-string> $targetTraits set to empty list to not filter by traits
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
    protected function parsePropertiesOfClass(string $class): array
    {
        if (!array_key_exists($class, $this->parsedClasses)) {
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
                        static function(TagWithType $tag) use ($typeResolver): ClassOrInterfaceType {
                            $tagType = $tag->getType();
                            Assert::notNull($tagType);
                            return ClassOrInterfaceType::fromType($tagType, $typeResolver);
                        },
                        $propertyTags
                    );

                    return array_filter($propertyTypes, [$this, 'isUsingRequiredTraits']);
                }, $this->targetTags);

                $this->parsedClasses[$class] = array_merge(...$nestedProperties);
            } catch (NoSourceException $exception) {
                return [];
            }
        }

        return $this->parsedClasses[$class];
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
        $nestedPropertiesByClass = array_map([$this, 'parsePropertiesOfClass'], $classes);

        return array_merge(...array_reverse($nestedPropertiesByClass));
    }

    /**
     * Accessed property classes must use all traits in {@link DocblockPropertyByTraitEvaluator::$targetTraits}.
     */
    protected function isUsingRequiredTraits(ClassOrInterfaceType $propertyType): bool
    {
        return $this->traitEvaluator->isClassUsingAllTraits($propertyType->getFullyQualifiedName(), $this->targetTraits);
    }
}
