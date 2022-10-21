<?php

declare(strict_types=1);

namespace Tests\Querying\Utilities;

use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\Utilities\Iterables;
use InvalidArgumentException;
use Tests\data\Model\Person;
use Tests\ModelBasedTest;

class IterablesTest extends ModelBasedTest
{
    public function testFlatWithArray(): void
    {
        $input = [
            [1, 2, 3],
            [4, 5, 6],
        ];

        $output = Iterables::mapFlat(static function (array $arrayElement): array {
            return $arrayElement;
        }, $input);

        self::assertEquals([1, 2, 3, 4, 5, 6], $output);
    }

    public function testFlatWithEmptyArray(): void
    {
        $input = [];

        $output = Iterables::mapFlat(static function (array $arrayElement): array {
            self::assertFalse(true);
            return [];
        }, $input);

        self::assertEquals([], $output);
    }

    public function testFlatWithObjects(): void
    {
        $output = Iterables::mapFlat(static function (Person $author): array {
            return Iterables::asArray($author->getBooks());
        }, array_values($this->authors));

        self::assertEquals(array_values($this->books), $output);
    }

    public function testRestructureIterableWithArrayNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Iterables::restructureNesting([], -1);
    }

    public function testRestructureIterableWithArray0(): void
    {
        $expected = [[[1, 2, 3], [4, 5, 6]]];

        $output = Iterables::restructureNesting([[1, 2, 3], [4, 5, 6]], 0);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray0WithValue(): void
    {
        $output = Iterables::restructureNesting(1, 0);
        self::assertEquals([1], $output);
    }

    public function testRestructureIterableWithArray1(): void
    {
        $expected = [
            [1, 2, 3],
            [4, 5, 6],
        ];

        $output = Iterables::restructureNesting([[1, 2, 3], [4, 5, 6]], 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Empty(): void
    {
        $expected = [];

        $output = Iterables::restructureNesting([], 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Value(): void
    {
        $expected = [1];

        $output = Iterables::restructureNesting(1, 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Deep(): void
    {
        $input = [[[1, 2, 3]], [[4, 5, 6]]];

        $expected = $input;

        $output = Iterables::restructureNesting($input, 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray2(): void
    {
        $input = [
            [
                [1], [2], [3]
            ],
            [
                [4], [5], [6]
            ],
        ];

        $expected = [
            [1], [2], [3], [4], [5], [6]
        ];

        $output = Iterables::restructureNesting($input, 2);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArrayAndAutostop(): void
    {
        $input = [
            [[1], [2], [3]],
            [[4], [5], [6]],
        ];

        $expected = [1, 2, 3, 4, 5, 6];

        $output = Iterables::restructureNesting($input, 999);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithIterable1(): void
    {
        $input = [
            new PropertyPath(null, '', 0, 'a', 'b', 'c'),
            new PropertyPath(null, '', 0, 'd', 'e', 'f'),
        ];

        $expected = $input;

        $output = Iterables::restructureNesting($input, 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithIterable2(): void
    {
        $input = [
            new PropertyPath(null, '', 0, 'a', 'b', 'c'),
            new PropertyPath(null, '', 0, 'd', 'e', 'f'),
        ];

        $expected = ['a', 'b', 'c', 'd', 'e', 'f'];

        $output = Iterables::restructureNesting($input, 2);

        self::assertEquals($expected, $output);
    }

    public function testSplitSingle(): void
    {
        $input = ['x' => 'a', 'y' => 'b', 'z' => 'c'];
        $expected = [
            ['x' => 'a', 'y' => 'b', 'z' => 'c'],
        ];
        $output = Iterables::split($input, 3);

        self::assertEquals($expected, $output);
    }

    public function testSplitEmpty(): void
    {
        $output = Iterables::split([]);
        self::assertEquals([], $output);
    }

    public function testSplitWithEmptiesOnly(): void
    {
        $output = Iterables::split([], 0, 0, 0);
        self::assertEquals([[], [], []], $output);
    }

    public function testSplitWithEmptiesInserted(): void
    {
        $input = [1, 2, 3];
        $expected = [[], [0 => 1], [], [1 => 2, 2 => 3], [], []];
        $output = Iterables::split($input, 0, 1, 0, 2, 0, 0);

        self::assertEquals($expected, $output);
    }

    public function testSplitWithEmptiesInsertedAndPreservedIntKeys(): void
    {
        $input = [1, 2, 3];
        $expected = [[], [1], [], [1 => 2, 2 => 3], [], []];
        $output = Iterables::split($input, 0, 1, 0, 2, 0, 0);

        self::assertEquals($expected, $output);
    }

    public function testSplitWithStringKeys(): void
    {
        $input = ['x' => 'a', 'y' => 'b', 'z' => 'c'];
        $expected = [['x' => 'a', 'y' => 'b'], ['z' => 'c']];
        $output = Iterables::split($input, 2, 1);

        self::assertEquals($expected, $output);
    }

    public function testSplitWithIntKeys(): void
    {
        $input = [3 => 'a', 7 => 'b', 1 => 'c'];
        $expected = [[3 => 'a', 7 => 'b'], [1 => 'c']];
        $output = Iterables::split($input, 2, 1);

        self::assertEquals($expected, $output);
    }

    public function testSplitWithPreservedStringKeys(): void
    {
        $input = ['x' => 'a', 'y' => 'b', 'z' => 'c'];
        $expected = [['x' => 'a', 'y' => 'b'], ['z' => 'c']];
        $output = Iterables::split($input, 2, 1);

        self::assertEquals($expected, $output);
    }

    public function testSplitWithPreservedIntKeys(): void
    {
        $input = [3 => 'a', 7 => 'b', 1 => 'c'];
        $expected = [[3 => 'a', 7 => 'b'], [1 => 'c']];
        $output = Iterables::split($input, 2, 1);

        self::assertEquals($expected, $output);
    }
}
