<?php

declare(strict_types=1);

namespace Tests\PathBuilding;

use EDT\PathBuilding\PropertyAutoPathTrait;
use EDT\PathBuilding\PropertyEvaluatorPool;
use EDT\PathBuilding\PropertyTag;
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
            [[], [PropertyTag::VAR]],
            [[], [PropertyTag::PROPERTY]],
            [[], [PropertyTag::PROPERTY_WRITE]],
            [[], [PropertyTag::PROPERTY_READ]],
            [[], [PropertyTag::PARAM]],

            [[], [
                PropertyTag::VAR,
                PropertyTag::PROPERTY,
                PropertyTag::PROPERTY_READ,
                PropertyTag::PROPERTY_WRITE,
                PropertyTag::PARAM
            ]],

            [[PropertyAutoPathTrait::class], [PropertyTag::VAR]],
            [[PropertyAutoPathTrait::class], [PropertyTag::PROPERTY]],
            [[PropertyAutoPathTrait::class], [PropertyTag::PROPERTY_WRITE]],
            [[PropertyAutoPathTrait::class], [PropertyTag::PROPERTY_READ]],
            [[PropertyAutoPathTrait::class], [PropertyTag::PARAM]],

            [[PropertyAutoPathTrait::class], [
                PropertyTag::VAR,
                PropertyTag::PROPERTY,
                PropertyTag::PROPERTY_READ,
                PropertyTag::PROPERTY_WRITE,
                PropertyTag::PARAM
            ]],
        ];
    }
}
