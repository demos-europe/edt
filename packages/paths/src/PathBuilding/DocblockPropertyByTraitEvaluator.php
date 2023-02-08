<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Parsing\Utilities\DocblockTagParser;
use EDT\Parsing\Utilities\ParseException;
use function array_key_exists;

class DocblockPropertyByTraitEvaluator
{
    /**
     * Cache of already parsed classes
     *
     * @var array<class-string, array<non-empty-string, class-string>>
     */
    private array $parsedClasses = [];

    /**
     * @param list<non-empty-string> $targetTraits
     * @param non-empty-list<PropertyTag> $targetTags The docblock tags to look for when parsing the docblock. Defaults to (effectively) &#64;property-read.
     */
    public function __construct(
        private readonly TraitEvaluator $traitEvaluator,
        private readonly array $targetTraits,
        private readonly array $targetTags
    ) {}

    /**
     * @param class-string $class
     *
     * @return array<non-empty-string, class-string>
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
     * @param class-string $class
     *
     * @return array<non-empty-string, class-string>
     *
     * @throws ParseException
     * @throws PropertyTagException
     */
    private function parsePropertiesOfClass(string $class): array
    {
        if (!array_key_exists($class, $this->parsedClasses)) {
            $parser = new DocblockTagParser($class);
            $nestedProperties = array_map(function (PropertyTag $targetTag) use ($parser): array {
                $propertyTags = $parser->getTags($targetTag->value);
                $propertyTags = array_map([$targetTag, 'convertToCorrespondingType'], $propertyTags);
                $propertyNames = array_map([$parser, 'getVariableNameOfTag'], $propertyTags);
                $propertyTags = array_combine($propertyNames, $propertyTags);
                $propertyTypes = array_map([$parser, 'getPropertyType'], $propertyTags);

                return array_filter($propertyTypes, [$this, 'isUsingRequiredTraits']);
            }, $this->targetTags);

            $this->parsedClasses[$class] = array_merge(...$nestedProperties);
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
     * @return array<non-empty-string, class-string>
     *
     * @throws ParseException
     */
    private function parsePropertiesOfClasses(array $classes): array
    {
        $nestedPropertiesByClass = array_map([$this, 'parsePropertiesOfClass'], $classes);

        return array_merge(...array_reverse($nestedPropertiesByClass));
    }

    /**
     * Accessed property classes must use all traits in {@link DocblockPropertyByTraitEvaluator::$targetTraits}.
     *
     * @param class-string $class
     */
    private function isUsingRequiredTraits(string $class): bool
    {
        return $this->traitEvaluator->isClassUsingAllTraits($class, $this->targetTraits);
    }
}
