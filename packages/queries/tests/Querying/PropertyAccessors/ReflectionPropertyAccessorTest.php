<?php

declare(strict_types=1);

namespace Tests\Querying\PropertyAccessors;

use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use Tests\ModelBasedTest;
use function array_slice;

class ReflectionPropertyAccessorTest extends ModelBasedTest
{
    /**
     * @var ReflectionPropertyAccessor
     */
    private $propertyAccessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyAccessor = new ReflectionPropertyAccessor();
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
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, 'books');
        self::assertEquals([$this->authors['rowling']], $value);
    }

    public function testGetValueByCuttedPath(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, 'books', 'title');
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertIsIterable($value[0]);
        self::assertCount(1, $value);
        self::assertEquals($expected, $value[0]);
    }

    public function testGetValueAsPrimitiveArray(): void
    {
        $books = $this->authors['rowling']->getBooks();
        $value = $this->propertyAccessor->getValuesByPropertyPath($books, 0, 'title');

        $expected = ['Harry Potter and the Philosopher\'s Stone', 'Harry Potter and the Deathly Hallows'];
        self::assertIsArray($value);
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetFlattedValue(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 0, 'books', 'title');

        $expected = ['Harry Potter and the Philosopher\'s Stone', 'Harry Potter and the Deathly Hallows'];
        self::assertIsArray($value);
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetValuesByPath(): void
    {
        $value = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 1, 'books');
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(2, $value);
        self::assertEquals($expected, $value);
    }

    public function testGetValuesByCuttedPath(): void
    {
        $values = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], -1, 'books', 'title');
        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $values);
        self::assertIsIterable($values[0]);
        self::assertEquals($expected, $values[0]);
    }

    public function testGetValuesByShortPath(): void
    {
        $values = $this->propertyAccessor->getValuesByPropertyPath($this->authors['rowling'], 0, 'books');

        $expected = [$this->books['philosopherStone'], $this->books['deathlyHallows']];
        self::assertCount(1, $values);
        self::assertIsIterable($values[0]);
        self::assertEquals($expected, $values[0]);
    }
}
