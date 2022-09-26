<?php

declare(strict_types=1);

namespace Tests\Querying\Utilities;

use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\Utilities\TableJoiner;
use ReflectionMethod;
use Tests\ModelBasedTest;

class TableJoinerTest extends ModelBasedTest
{
    /**
     * @var TableJoiner
     */
    private $tableJoiner;

    /**
     * @var ReflectionMethod
     */
    private $cartesianProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $this->cartesianProduct = new ReflectionMethod($this->tableJoiner, 'cartesianProduct');
        $this->cartesianProduct->setAccessible(true);
    }

    public function testCartesianProductWithNoColumns(): void
    {
        $actual = $this->cartesianProduct->invoke($this->tableJoiner, []);
        self::assertEquals([], $actual);
    }

    public function testCartesianProductWithOneColumn(): void
    {
        $input = [
            ['a', 'b', 'c'],
        ];
        $expected = [
            ['a'],
            ['b'],
            ['c'],
        ];
        $actual = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        self::assertEquals($expected, $actual);
    }

    public function testCartesianProductWithTwoColumns(): void
    {
        $input = [
            ['a', 'b', 'c'],
            [1, 2, 3],
        ];
        $expected = [
            ['a', 1],
            ['b', 1],
            ['c', 1],
            ['a', 2],
            ['b', 2],
            ['c', 2],
            ['a', 3],
            ['b', 3],
            ['c', 3],
        ];
        $actual = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        self::assertEquals($expected, $actual);
    }

    public function testGetValueRowsWithMergedPaths(): void
    {
        $bookPath = new PropertyPath(null, '', PropertyPath::DIRECT, 'books');
        $valueRows = $this->tableJoiner->getValueRows($this->authors['rowling'], [$bookPath]);
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $valueRows);
        $valueRow = $valueRows[0];
        self::assertIsArray($valueRow);
        self::assertCount(1, $valueRow);
        $value = $valueRow[0];
        self::assertIsIterable($value);
        self::assertEquals($expected, $value);
    }

    public function testGetValueRows(): void
    {
        $bookPath = new PropertyPath(null, '', PropertyPath::DIRECT, 'books');
        $valueRows = $this->tableJoiner->getValueRows($this->authors['rowling'], [$bookPath]);
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $valueRows);
        $valueRow = $valueRows[0];
        self::assertIsArray($valueRow);
        self::assertCount(1, $valueRow);
        $value = $valueRow[0];
        self::assertIsIterable($value);
        self::assertEquals($expected, $value);
    }

    public function testCartesianWithFirstColumnReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectDeprecationMessage("De-referencing '0' led to another reference '0'.");
        $input = [
            0,
            [false, true],
            ['abc'],
        ];

        $this->cartesianProduct->invoke($this->tableJoiner, $input);
    }

    public function testCartesianWithMissingReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectDeprecationMessage("Could not de-reference: missing index '3'.");
        $input = [
            [false, true],
            ['abc'],
            3,
        ];

        $this->cartesianProduct->invoke($this->tableJoiner, $input);
    }

    public function testCartesianWithSingleEmptyRow(): void
    {
        $input = [
            [],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [];

        self::assertEquals($expected, $output);
    }

    public function testCartesianWithSecondEmptyRow(): void
    {
        $input = [
            ['abc'],
            [],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [
            ['abc', null],
        ];

        self::assertEquals($expected, $output);
    }

    public function testCartesianWithFirstRowEmpty(): void
    {
        $input = [
            [],
            [1],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [
            [null, 1],
        ];

        self::assertEquals($expected, $output);
    }

    public function testCartesianWithReferenceAfterEmpty(): void
    {
        $input = [
            [],
            0,
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [];

        self::assertEquals($expected, $output);
    }

    public function testCartesianWithLastRowEmpty(): void
    {
        $input = [
            [1],
            [],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [
            [1, null],
        ];

        self::assertEquals($expected, $output);
    }

    public function testCartesionThreeEmpty(): void
    {
        $input = [
            [],
            [],
            [],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [];

        self::assertEquals($expected, $output);
    }

    public function testCartesianWithFirstRowEmptyAndThirdRowReference(): void
    {
        $input = [
            [],
            [1],
            0,
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);
        $expected = [
            [null, 1, null],
        ];

        self::assertEquals($expected, $output);
    }


    public function testCartesianWithEmptyRowBetween(): void
    {
        $input = [
            [false],
            ['abc'],
            [],
            1,
            0,
            [7],
        ];

        $output = $this->cartesianProduct->invoke($this->tableJoiner, $input);

        $expected = [
            [false, 'abc', null, 'abc', false, 7],
        ];

        self::assertEquals($expected, $output);
    }
}
