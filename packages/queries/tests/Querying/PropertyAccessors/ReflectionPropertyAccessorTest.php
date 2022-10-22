<?php

declare(strict_types=1);

namespace Tests\Querying\PropertyAccessors;

use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\PropertyPaths\PropertyPath;
use InvalidArgumentException;
use Tests\ModelBasedTest;
use function array_slice;

class ReflectionPropertyAccessorTest extends ModelBasedTest
{
    private ReflectionPropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyAccessor = new ReflectionPropertyAccessor();
        $this->restructureNesting = new \ReflectionMethod($this->propertyAccessor, 'restructureNesting');
        $this->restructureNesting->setAccessible(true);
    }

    public function testSliceBehavior(): void
    {
        $input = [1, 2, 3];
        $output = array_slice($input, 0, -1);
        self::assertEquals([1, 2], $output);
        $output = array_slice($input, 0, -100);
        self::assertEquals([], $output);
    }

    public function testGetValueByNegativeDepth(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, ['books']);
        self::assertEquals([$this->authors['rowling']], $value);
    }

    public function testGetValueByCuttedPath(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, ['books', 'title']);
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertIsIterable($value[0]);
        self::assertCount(1, $value);
        self::assertEquals($expected, $value[0]);
    }

    public function testGetValueAsPrimitiveArray(): void
    {
        $books = $this->authors['rowling']->getBooks();
        $value = $this->propertyAccessor->getValuesByPropertyPath($books, 0, ['title']);

        $expected = ['Harry Potter and the Philosopher\'s Stone', 'Harry Potter and the Deathly Hallows'];
        self::assertIsArray($value);
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetFlattedValue(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 0, ['books', 'title']);

        $expected = ['Harry Potter and the Philosopher\'s Stone', 'Harry Potter and the Deathly Hallows'];
        self::assertIsArray($value);
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetValuesByPath(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 1, ['books']);
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetValuesByCuttedPath(): void
    {
        $values = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, ['books', 'title']);
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $values);
        self::assertIsIterable($values[0]);
        self::assertEquals($expected, $values[0]);
    }

    public function testGetValuesByShortPath(): void
    {
        $values = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 0, ['books']);

        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $values);
        self::assertIsIterable($values[0]);
        self::assertEquals($expected, $values[0]);
    }

    public function testRestructureIterableWithArrayNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->restructureNesting->invoke($this->propertyAccessor, [], -1);
    }

    public function testRestructureIterableWithArray0(): void
    {
        $expected = [[[1, 2, 3], [4, 5, 6]]];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, [[1, 2, 3], [4, 5, 6]], 0);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray0WithValue(): void
    {
        $output = $this->restructureNesting->invoke($this->propertyAccessor, 1, 0);
        self::assertEquals([1], $output);
    }

    public function testRestructureIterableWithArray1(): void
    {
        $expected = [
            [1, 2, 3],
            [4, 5, 6],
        ];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, [[1, 2, 3], [4, 5, 6]], 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Empty(): void
    {
        $expected = [];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, [], 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Value(): void
    {
        $expected = [1];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, 1, 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArray1Deep(): void
    {
        $input = [[[1, 2, 3]], [[4, 5, 6]]];

        $expected = $input;

        $output = $this->restructureNesting->invoke($this->propertyAccessor, $input, 1);

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

        $output = $this->restructureNesting->invoke($this->propertyAccessor, $input, 2);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithArrayAndAutostop(): void
    {
        $input = [
            [[1], [2], [3]],
            [[4], [5], [6]],
        ];

        $expected = [1, 2, 3, 4, 5, 6];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, $input, 999);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithIterable1(): void
    {
        $input = [
            new PropertyPath(null, '', 0, 'a', 'b', 'c'),
            new PropertyPath(null, '', 0, 'd', 'e', 'f'),
        ];

        $expected = $input;

        $output = $this->restructureNesting->invoke($this->propertyAccessor, $input, 1);

        self::assertEquals($expected, $output);
    }

    public function testRestructureIterableWithIterable2(): void
    {
        $input = [
            new PropertyPath(null, '', 0, 'a', 'b', 'c'),
            new PropertyPath(null, '', 0, 'd', 'e', 'f'),
        ];

        $expected = ['a', 'b', 'c', 'd', 'e', 'f'];

        $output = $this->restructureNesting->invoke($this->propertyAccessor, $input, 2);

        self::assertEquals($expected, $output);
    }
}
