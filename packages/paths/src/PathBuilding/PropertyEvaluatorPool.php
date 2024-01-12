<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use function array_key_exists;

class PropertyEvaluatorPool
{
    /**
     * @var array<string, DocblockPropertyByTraitEvaluator>
     */
    protected array $evaluators = [];

    protected TraitEvaluator $traitEvaluator;

    protected static ?PropertyEvaluatorPool $instance = null;

    protected function __construct()
    {
        $this->traitEvaluator = new TraitEvaluator();
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param list<trait-string> $requiredTraits
     * @param non-empty-list<PropertyTag> $targetTags
     */
    public function getEvaluator(array $requiredTraits, array $targetTags): DocblockPropertyByTraitEvaluator
    {
        $targetTagNames = array_map(
            static fn (PropertyTag $targetTag): string => $targetTag->name,
            $targetTags
        );
        $tagsConcat = implode('|', $targetTagNames);
        $traitsConcat = implode('|', $requiredTraits);
        $evaluatorKey = "$tagsConcat&$traitsConcat";
        if (!array_key_exists($evaluatorKey, $this->evaluators)) {
            $this->evaluators[$evaluatorKey] = new DocblockPropertyByTraitEvaluator(
                $this->traitEvaluator,
                $requiredTraits,
                $targetTags
            );
        }

        return $this->evaluators[$evaluatorKey];
    }
}
