<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Parsing\Utilities\DocblockTagParser;
use EDT\Parsing\Utilities\ParseException;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use function array_key_exists;
use function Safe\array_combine;

class DocblockPropertyByTraitEvaluator
{
    /**
     * @var non-empty-string
     */
    private string $targetTrait;
    /**
     * The docblock tags to look for when parsing the docblock. Defaults to (effectively) &#64;property-read.
     *
     * @var non-empty-list<non-empty-string>
     */
    private array $targetTags;

    private TraitEvaluator $traitEvaluator;

    /**
     * Cache of already parsed classes
     *
     * @var array<class-string, array<non-empty-string, class-string>>
     */
    private array $parsedClasses = [];

    /**
     * @param non-empty-string                 $targetTrait
     * @param non-empty-list<non-empty-string> $targetTags
     */
    public function __construct(TraitEvaluator $traitEvaluator, string $targetTrait, array $targetTags)
    {
        $this->targetTrait = $targetTrait;
        $this->targetTags = $targetTags;
        $this->traitEvaluator = $traitEvaluator;
    }

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
     * @return non-empty-list<non-empty-string>
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
     */
    private function parsePropertiesOfClass(string $class): array
    {
        if (!array_key_exists($class, $this->parsedClasses)) {
            $parser = new DocblockTagParser($class);
            $nestedProperties = array_map(function (string $targetTag) use ($parser): array {
                $tags = $parser->getTags($targetTag);
                $tags = array_map(static function (Tag $tag): TagWithType {
                    if (!$tag instanceof PropertyRead
                        && !$tag instanceof PropertyWrite
                        && !$tag instanceof Property
                        && !$tag instanceof Param
                        && !$tag instanceof Var_) {
                        throw new \InvalidArgumentException("Can not determine variable name for '{$tag->getName()}' tags.");
                    }

                    return $tag;
                }, $tags);
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
     * Accessed property classes must use {@link DocblockPropertyByTraitEvaluator::$targetTrait}.
     *
     * @param class-string $class
     */
    private function isUsingTrait(string $class): bool
    {
        return $this->traitEvaluator->isClassUsingTrait($class, $this->targetTrait);
    }
}
