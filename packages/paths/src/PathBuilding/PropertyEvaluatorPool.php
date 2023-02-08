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
     * @param list<non-empty-string> $requiredTraits
     * @param non-empty-list<non-empty-string> $targetTags
     *
     * @return DocblockPropertyByTraitEvaluator
     */
    public function getEvaluator(array $requiredTraits, array $targetTags): DocblockPropertyByTraitEvaluator
    {
        $tagsConcat = implode('|', $targetTags);
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
