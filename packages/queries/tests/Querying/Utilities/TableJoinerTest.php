<?php

declare(strict_types=1);

namespace Tests\Querying\Utilities;

use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\Utilities\TableJoiner;
use InvalidArgumentException;
use ReflectionMethod;
use Tests\data\Model\Publisher;
use Tests\ModelBasedTest;
use function PHPUnit\Framework\assertCount;

class TableJoinerTest extends ModelBasedTest
{
    private TableJoiner $tableJoiner;

    private ReflectionMethod $cartesianProduct;

    private ReflectionMethod $setReferences;

    private ReflectionMethod $setDeReferencing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableJoiner = new TableJoiner(new ReflectionPropertyAccessor());
        $this->cartesianProduct = $this->getReflectionMethod('cartesianProduct');
        $this->setReferences = $this->getReflectionMethod('setReferencesGeneric');
        $this->setDeReferencing = $this->getReflectionMethod('setDeReferencing');
        $this->insertValue = $this->getReflectionMethod('insertValue');
    }

    /**
     * @param non-empty-list<list<mixed>|int<0, max>> $inputColumns
     * @param list<non-empty-list<mixed>> $expectedOutput
     *
     * @dataProvider cartesianProductTestData
     */
    public function testCartesianProduct(array $inputColumns, array $expectedOutput): void
    {
        $actual = $this->cartesianProduct->invoke($this->tableJoiner, $inputColumns);
        self::assertEquals($expectedOutput, $actual);
    }

    /**
     * Very basic {@link TableJoiner::cartesianProduct()} performance test code.
     *
     * @dataProvider cartesianProductPerformanceTestData
     */
    public function testCartesianProductWithGeneratedInput(array $columnSizes, float $maxRunTimeMs): void
    {
        $this->markTestSkipped('for manual execution only');
        $inputColumns = array_map(
            static fn (int $rowCount): array => array_map(
                static fn (int $rowIndex): string => "r$rowIndex",
                range(1, $rowCount)
            ),
            $columnSizes
        );

        $start = microtime(true);
        $actual = $this->cartesianProduct->invoke($this->tableJoiner, $inputColumns);
        $end = microtime(true);
        assertCount(array_product($columnSizes), $actual);
        self::assertLessThanOrEqual($maxRunTimeMs, $end-$start);
    }

    public function testGetValueRowsWithMergedPaths(): void
    {
        $bookPath = new PropertyPath(null, '', PropertyPath::DIRECT, ['books']);
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
        $bookPath = new PropertyPath(null, '', PropertyPath::DIRECT, ['books']);
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
        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectDeprecationMessage("Could not de-reference: missing index '3'.");
        $input = [
            [false, true],
            ['abc'],
            3,
        ];

        $this->cartesianProduct->invoke($this->tableJoiner, $input);
    }

    public function testSetReferencesSingle(): void
    {
        $input = ['a', 'b', 'c', 'a'];

        $references = $this->setReferences->invoke(
            $this->tableJoiner,
            static fn (string $a, string $b): bool => $a === $b,
            $input
        );
        $expected = ['a', 'b', 'c', 0];

        self::assertEquals($expected, $references);

        $deReferenced = $this->setDeReferencing->invoke($this->tableJoiner, [['a'], ['b'], ['c'], 0]);

        self::assertEquals([['a'], ['b'], ['c'], ['a']], $deReferenced);
    }

    public function testSetReferencesMulti(): void
    {
        $input = ['a', 'a', 'c', 'a'];

        $references = $this->setReferences->invoke(
            $this->tableJoiner,
            static fn (string $a, string $b): bool => $a === $b,
            $input
        );
        $expected = ['a', 0, 'c', 0];

        self::assertEquals($expected, $references);

        $deReferenced = $this->setDeReferencing->invoke($this->tableJoiner, [['a'], 0, ['c'], 0]);

        self::assertEquals([['a'], ['a'], ['c'], ['a']], $deReferenced);
    }

    public function testSetReferencesAll(): void
    {
        $input = ['a', 'a', 'a', 'a'];

        $references = $this->setReferences->invoke(
            $this->tableJoiner,
            static fn (string $a, string $b): bool => $a === $b,
            $input
        );
        $expected = ['a', 0, 0, 0];

        self::assertEquals($expected, $references);

        $deReferenced = $this->setDeReferencing->invoke($this->tableJoiner, [['a'], 0, 0, 0]);

        self::assertEquals([['a'], ['a'], ['a'], ['a']], $deReferenced);
    }

    public function testInsertValue(): void
    {
        $input = [[1, 2, 3], [4, 5, 6]];

        $this->insertValue->invokeArgs($this->tableJoiner, [&$input, 1, 'x']);
        $expected = [[1, 'x', 2, 3], [4, 'x', 5, 6]];

        self::assertEquals($expected, $input);
    }

    public function testInsertValueAtEnd(): void
    {
        $input = [['a'], ['b']];

        $this->insertValue->invokeArgs($this->tableJoiner, [&$input, 1, 'x']);
        $expected = [['a', 'x'], ['b', 'x']];

        self::assertEquals($expected, $input);
    }

    public function testInsertNullAtEnd(): void
    {
        $input = [['a'], ['b']];

        $this->insertValue->invokeArgs($this->tableJoiner, [&$input, 1, null]);
        $expected = [['a', null], ['b', null]];

        self::assertEquals($expected, $input);
    }

    public function cartesianProductPerformanceTestData(): array
    {
        return [
            [[1000, 50, 25], 0.5],
        ];
    }

    public function cartesianProductTestData(): array
    {
        return [
            [[], []], // no columns
            [[['a', 'b', 'c']], [ // one column
                ['a'],
                ['b'],
                ['c'],
            ]],
            [[ // two columns
                ['a', 'b', 'c'],
                [1, 2, 3],
            ], [
                ['a', 1],
                ['b', 1],
                ['c', 1],
                ['a', 2],
                ['b', 2],
                ['c', 2],
                ['a', 3],
                ['b', 3],
                ['c', 3],
            ]],
            [[ // single empty row
                [],
            ], []],
            [[ // second empty row
                ['abc'],
                [],
            ], [
                ['abc', null],
            ]],
            [[ // first row empty
                [],
                [1],
            ], [
                [null, 1],
            ]],
            [[ // reference after empty
                [],
                0,
            ], []],
            [[ // last row empty
                [1],
                [],
            ], [
                [1, null],
            ]],
            [[ // three empty
                [],
                [],
                [],
            ], []],
            [[ // first row empty and third row reference
                [],
                [1],
                0,
            ], [
                [null, 1, null],
            ]],
            [[ // empty row between
                [false],
                ['abc'],
                [],
                1,
                0,
                [7],
            ], [
                [false, 'abc', null, 'abc', false, 7],
            ]],
        ];
    }

    private function getReflectionMethod(string $name): ReflectionMethod
    {
        $method = new ReflectionMethod($this->tableJoiner, $name);
        $method->setAccessible(true);

        return $method;
    }
}
