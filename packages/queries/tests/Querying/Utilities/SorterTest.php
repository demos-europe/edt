<?php

declare(strict_types=1);

namespace Tests\Querying\Utilities;

use EDT\Querying\Contracts\SortException;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\Utilities\Sorter;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\Utilities\TableJoiner;
use Tests\ModelBasedTest;

class SorterTest extends ModelBasedTest
{
    private PhpSortMethodFactory $sortMethodFactory;

    private Sorter $sorter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sortMethodFactory = new PhpSortMethodFactory();
        $this->sorter = new Sorter(new TableJoiner(new ReflectionPropertyAccessor()));
    }

    public function testTitlePropertyAscending(): void
    {
        $titleSorting = $this->sortMethodFactory->propertyAscending(['title']);
        $sortedBooks = $this->sorter->sortArray($this->books, [$titleSorting]);
        $expected = [
            $this->books['beowulf'],
            $this->books['doctorSleep'],
            $this->books['deathlyHallows'],
            $this->books['philosopherStone'],
            $this->books['pickwickPapers'],
        ];
        self::assertEquals($expected, $sortedBooks);
    }

    public function testTitlePropertyDescending(): void
    {
        $titleSorting = $this->sortMethodFactory->propertyDescending(['title']);
        $sortedBooks = $this->sorter->sortArray($this->books, [$titleSorting]);
        $expected = array_reverse([
            $this->books['beowulf'],
            $this->books['doctorSleep'],
            $this->books['deathlyHallows'],
            $this->books['philosopherStone'],
            $this->books['pickwickPapers'],
        ]);
        self::assertEquals($expected, $sortedBooks);
    }

    public function testBirthdayPropertyDescending(): void
    {
        $birthdaySorting = $this->sortMethodFactory->propertyAscending(['author', 'birth', 'day']);
        $sortedBooks = $this->sorter->sortArray($this->books, [$birthdaySorting]);
        $expected = [
            $this->books['beowulf'],
            $this->books['pickwickPapers'],
            $this->books['doctorSleep'],
            $this->books['philosopherStone'],
            $this->books['deathlyHallows'],
        ];
        self::assertEquals($expected, $sortedBooks);
    }

    public function testNullComparisonAscending(): void
    {
        $pseudonymSorting = $this->sortMethodFactory->propertyAscending(['author', 'pseudonym']);
        $titleSorting = $this->sortMethodFactory->propertyAscending(['title']);
        $sortedBooks = $this->sorter->sortArray($this->books, [$pseudonymSorting, $titleSorting]);
        $expected = [
            $this->books['beowulf'],
            $this->books['pickwickPapers'],
            $this->books['doctorSleep'],
            $this->books['deathlyHallows'],
            $this->books['philosopherStone'],
        ];
        self::assertEquals($expected, $sortedBooks);
    }

    public function testNullComparisonDescending(): void
    {
        $pseudonymSorting = $this->sortMethodFactory->propertyDescending(['author', 'pseudonym']);
        $titleSorting = $this->sortMethodFactory->propertyDescending(['title']);
        $sortedBooks = $this->sorter->sortArray($this->books, [$pseudonymSorting, $titleSorting]);
        $expected = array_reverse([
            $this->books['beowulf'],
            $this->books['pickwickPapers'],
            $this->books['doctorSleep'],
            $this->books['deathlyHallows'],
            $this->books['philosopherStone'],
        ]);
        self::assertEquals($expected, $sortedBooks);
    }

    public function testObjectComparison(): void
    {
        $this->expectException(SortException::class);
        $authorSorting = $this->sortMethodFactory->propertyDescending(['author']);
        $this->sorter->sortArray($this->books, [$authorSorting]);
    }

    public function testUnsupportedPath(): void
    {
        $this->expectException(SortException::class);
        $titleSorting = $this->sortMethodFactory->propertyAscending(['books', 'title']);
        $this->sorter->sortArray($this->authors, [$titleSorting]);
    }

    public function testFailOrderSmallSetRestrict(): void
    {
        $pseudonymSorting = $this->sortMethodFactory->propertyDescending(['author', 'pseudonym']);
        $titleSorting = $this->sortMethodFactory->propertyDescending(['title']);
        $input = [
            0 => $this->books['beowulf'],
            1 => $this->books['pickwickPapers'],
        ];
        $sortedBooks = $this->sorter->sortArray($input, [$pseudonymSorting, $titleSorting]);
        $expected = [
            0 => $this->books['beowulf'],
            1 => $this->books['pickwickPapers'],
        ];
        self::assertNotEquals($expected, $sortedBooks);
    }

    public function testSmallSetRestrict(): void
    {
        $pseudonymSorting = $this->sortMethodFactory->propertyDescending(['author', 'pseudonym']);
        $titleSorting = $this->sortMethodFactory->propertyDescending(['title']);
        $input = [
            1 => $this->books['beowulf'],
            0 => $this->books['pickwickPapers'],
        ];
        $sortedBooks = $this->sorter->sortArray($input, [$pseudonymSorting, $titleSorting]);
        $expected = [
            0 => $this->books['pickwickPapers'],
            1 => $this->books['beowulf'],
        ];
        self::assertEquals($expected, $sortedBooks);
    }
}
