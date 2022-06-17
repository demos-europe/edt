<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use function array_key_exists;

class PropertyEvaluatorPool
{
    /**
     * @var array<string, DocblockPropertyByTraitEvaluator>
     */
    protected $evaluators = [];

    /**
     * @var TraitEvaluator
     */
    protected $traitEvaluator;

    /**
     * @var self|null
     */
    protected static $instance;

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

    public function getEvaluator(string $targetTrait, string $targetTag = 'property-read', string ...$targetTags): DocblockPropertyByTraitEvaluator
    {
        $key = $targetTags;
        array_push($key, $targetTag, $targetTrait);
        $key = implode('|', $key);
        if (!array_key_exists($key, $this->evaluators)) {
            $this->evaluators[$key] = new DocblockPropertyByTraitEvaluator(
                $this->traitEvaluator,
                $targetTrait,
                $targetTag,
                ...$targetTags
            );
        }

        return $this->evaluators[$key];
    }
}
