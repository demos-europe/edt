<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\DocblockPropertyByTraitEvaluator;
use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathTrait;
use EDT\PathBuilding\PropertyEvaluatorPool;

/**
 * @param End $paramAttribute
 * @param FooResource $paramRelationship
 * @property-write End $propertyWriteAttribute
 * @property-write FooResource $propertyWriteRelationship
 * @var End $varAttribute
 * @var FooResource $varRelationship
 * @property End $propertyAttribute
 * @property FooResource $propertyRelationship
 */
class VarTagged
{
    use PropertyAutoPathTrait;

    protected function getDocblockTraitEvaluator(): DocblockPropertyByTraitEvaluator
    {
        if (null === $this->docblockTraitEvaluator) {
            $this->docblockTraitEvaluator = PropertyEvaluatorPool::getInstance()->getEvaluator(PropertyAutoPathTrait::class, 'var');
        }

        return $this->docblockTraitEvaluator;
    }
}
