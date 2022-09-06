<?php

declare(strict_types=1);

namespace Tests\Schema;

use EDT\JsonApi\Schema\Cardinality;
use PHPUnit\Framework\TestCase;

class CardinalityTest extends TestCase
{
    public function testGetToMany()
    {
        $cardinality = Cardinality::getToMany();
        self::assertTrue($cardinality->isToMany());
        self::assertFalse($cardinality->isToOne());
    }

    public function testIsToMany()
    {
        $cardinality = Cardinality::getToOne();
        self::assertFalse($cardinality->isToMany());
        self::assertTrue($cardinality->isToOne());
    }
}
