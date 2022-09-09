<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Parsing\Utilities\DocblockTagParser;
use EDT\Parsing\Utilities\ParseException;
use function array_key_exists;
use function Safe\array_combine;

class DocblockPropertyByTraitEvaluator
{
    /**
     * @var string
     */
    private $targetTrait;
    /**
     * The docblock tags to look for when parsing the docblock. Defaults to (effectively) &#64;property-read.
     *
     * @var array<int,string>
     */
    private $targetTags;

    /**
     * @var TraitEvaluator
     */
    private $traitEvaluator;

    /**
     * Cache of already parsed classes
     *
     * @var array<class-string, array<string, class-string>>
     */
    private $parsedClasses = [];

    public function __construct(TraitEvaluator $traitEvaluator, string $targetTrait, string $targetTag = 'property-read', string ...$targetTags)
    {
        $this->targetTrait = $targetTrait;
        $this->targetTags = $targetTags;
        array_unshift($this->targetTags, $targetTag);
        $this->traitEvaluator = $traitEvaluator;
    }

    /**
     * @return array<string, class-string>
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

        return $this->parsePropertiesOfClasses(...$classes);
    }

    /**
     * @return array<int, string>
     */
    public function getTargetTags(): array
    {
        return $this->targetTags;
    }

    /**
     * @param class-string $class
     *
     * @return array<string, class-string>
     *
     * @throws ParseException
     */
    private function parsePropertiesOfClass(string $class): array
    {
        if (!array_key_exists($class, $this->parsedClasses)) {
            $parser = new DocblockTagParser($class);
            $nestedProperties = array_map(function (string $targetTag) use ($parser): array {
                $tags = $parser->getTags($targetTag);
                $keys = array_map([$parser, 'getVariableNameOfTag'], $tags);
                $tags = array_combine($keys, $tags);
                $tags = array_map([$parser, 'getTagType'], $tags);

                return array_filter($tags, [$this, 'isUsingTrait']);
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
     * @param class-string ...$classes
     *
     * @return array<string, class-string>
     *
     * @throws ParseException
     */
    private function parsePropertiesOfClasses(string $class, string ...$classes): array
    {
        array_unshift($classes, $class);
        $nestedPropertiesByClass = array_map([$this, 'parsePropertiesOfClass'], $classes);

        return array_merge(...array_reverse($nestedPropertiesByClass));
    }

    /**
     * Accessed property classes must use {@link DocblockPropertyByTraitEvaluator::$targetTrait}.
     *
     * @param class-string $class
     */
    private function isUsingTrait(string $class): bool
    {
        return $this->traitEvaluator->isClassUsingTrait($class, $this->targetTrait);
    }
}
