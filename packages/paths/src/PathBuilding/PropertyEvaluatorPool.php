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
     * @param non-empty-string $targetTrait
     * @param non-empty-list<non-empty-string> $targetTags
     *
     * @return DocblockPropertyByTraitEvaluator
     */
    public function getEvaluator(string $targetTrait, array $targetTags): DocblockPropertyByTraitEvaluator
    {
        $key = $targetTags;
        $key[] = $targetTrait;
        $key = implode('|', $key);
        if (!array_key_exists($key, $this->evaluators)) {
            $this->evaluators[$key] = new DocblockPropertyByTraitEvaluator(
                $this->traitEvaluator,
                $targetTrait,
                $targetTags,
            );
        }

        return $this->evaluators[$key];
    }
}
