<?php

declare(strict_types=1);

namespace Tests\PathBuilding;

use EDT\PathBuilding\PropertyAutoPathTrait;
use EDT\PathBuilding\PropertyEvaluatorPool;
use PHPUnit\Framework\TestCase;

class PropertyEvaluatorPoolTest extends TestCase
{
    /**
     * @dataProvider getGetEvaluatorParams()
     */
    public function testGetEvaluator($requiredTraits, $parsedTags): void
    {
        PropertyEvaluatorPool::getInstance()->getEvaluator($requiredTraits, $parsedTags);

        // no exception
        self::assertTrue(true);
    }

    public function getGetEvaluatorParams()
    {
        return [
            [[], ['var']],
            [[], ['property']],
            [[], ['property-write']],
            [[], ['property-read']],
            [[], ['param']],

            [[], ['var', 'property', 'property-read', 'property-write', 'param']],

            [[PropertyAutoPathTrait::class], ['var']],
            [[PropertyAutoPathTrait::class], ['property']],
            [[PropertyAutoPathTrait::class], ['property-write']],
            [[PropertyAutoPathTrait::class], ['property-read']],
            [[PropertyAutoPathTrait::class], ['param']],

            [[PropertyAutoPathTrait::class], ['var', 'property', 'property-read', 'property-write', 'param']],
        ];
    }
}
