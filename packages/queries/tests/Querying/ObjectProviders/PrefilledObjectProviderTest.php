<?php

namespace Tests\Querying\ObjectProviders;

use EDT\Querying\Contracts\SliceException;
use EDT\Querying\ObjectProviders\PrefilledObjectProvider;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use Tests\data\Model\Person;
use Tests\ModelBasedTest;

class PrefilledObjectProviderTest extends ModelBasedTest
{
    /**
     * @var PrefilledObjectProvider<Person, int>
     */
    private $authorProvider;

    public function testUnconditionedAll()
    {
        $actual = $this->authorProvider->getObjects([]);
        self::assertEquals($this->authors, $actual);
    }

    public function testUndconditionedOffsetSlice()
    {
        $actual = $this->authorProvider->getObjects([], [], 1);
        $expected = $this->authors;
        array_shift($expected);
        self::assertEquals($expected, $actual);
    }

    public function testUndconditionedLimitSlice()
    {
        $actual = $this->authorProvider->getObjects([], [], 0, 2);
        $expected = $this->authors;
        $expected = [
            array_shift($expected),
            array_shift($expected),
        ];
        self::assertEquals($expected, array_values($actual));
    }

    public function testUndconditionedOffsetAndLimitSlice()
    {
        $actual = $this->authorProvider->getObjects([], [], 1, 2);
        $expected = $this->authors;
        array_shift($expected);
        $expected = [
            array_shift($expected),
            array_shift($expected),
        ];
        self::assertEquals($expected, array_values($actual));
    }

    public function testNegativeOffset()
    {
        $this->expectException(SliceException::class);
        $this->authorProvider->getObjects([], [], -1);
    }

    public function testNegativeLimit()
    {
        $this->expectException(SliceException::class);
        $this->authorProvider->getObjects([], [], 0, -1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $propertyAccessor = new ReflectionPropertyAccessor();
        $this->authorProvider = new PrefilledObjectProvider($propertyAccessor, $this->authors);
    }
}
