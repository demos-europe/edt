<?php

declare(strict_types=1);

namespace Tests\Querying\Evaluators;

use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\Functions\InvertedBoolean;
use EDT\Querying\Functions\StringContains;
use EDT\Querying\Functions\AllEqual;
use EDT\Querying\Functions\Product;
use EDT\Querying\Functions\Value;
use EDT\Querying\Functions\LowerCase;
use EDT\Querying\Functions\Property;
use EDT\Querying\Functions\Size;
use EDT\Querying\Functions\Sum;
use EDT\Querying\Functions\UpperCase;
use EDT\Querying\PropertyPaths\PropertyPath;
use EDT\Querying\Utilities\ConditionEvaluator;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\PropertyAccessors\ReflectionPropertyAccessor;
use EDT\Querying\Utilities\TableJoiner;
use Tests\ModelBasedTest;

class ConditionEvaluatorTest extends ModelBasedTest
{
    private PhpConditionFactory $conditionFactory;

    private ConditionEvaluator $conditionEvaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conditionFactory = new PhpConditionFactory();
        $propertyAccessor = new ReflectionPropertyAccessor();
        $this->conditionEvaluator = new ConditionEvaluator(new TableJoiner($propertyAccessor));
    }

    public function testAllConditionsApplyWithOne(): void
    {
        $propertyIsNull = $this->conditionFactory->propertyIsNull(['author', 'pseudonym']);
        $this->conditionFactory->allConditionsApply($propertyIsNull);
        $expectedBooks = [$this->books['beowulf']];
        $filteredBooks = array_values($this->conditionEvaluator->filterArray($this->books, $propertyIsNull));
        self::assertEquals($expectedBooks, $filteredBooks);
    }

    public function testAllConditionsApplyWithTwo(): void
    {
        $nullPseudonym = $this->conditionFactory->propertyIsNull(['pseudonym']);
        $usaBirthplace = $this->conditionFactory->propertyHasValue('USA', ['birth', 'country']);
        $allConditionsApply = $this->conditionFactory->allConditionsApply($nullPseudonym, $usaBirthplace);
        $expectedBooks = [$this->authors['lee'], $this->authors['salinger']];
        $filteredBooks = array_values($this->conditionEvaluator->filterArray($this->authors, $allConditionsApply));
        self::assertEquals($expectedBooks, $filteredBooks);
    }

    public function testAlwaysTrue(): void
    {
        $alwaysTrue = $this->conditionFactory->true();
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $alwaysTrue);
        self::assertEquals($this->authors, $filteredAuthors);
    }

    public function testAlwaysFalse(): void
    {
        $alwaysFalse = $this->conditionFactory->false();
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $alwaysFalse);
        self::assertEquals([], $filteredAuthors);
    }

    public function testAnyConditionApplies(): void
    {
        $kingAuthor = $this->conditionFactory->propertyHasValue('Stephen King', ['name']);
        $tolkienAuthor = $this->conditionFactory->propertyHasValue('John Ronald Reuel Tolkien', ['name']);
        $kingOrTolkien = $this->conditionFactory->anyConditionApplies($kingAuthor, $tolkienAuthor);
        $expectedAuthors = [$this->authors['king'], $this->authors['tolkien']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $kingOrTolkien));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testInvertedConditionApplies(): void
    {
        $alwaysTrue = new InvertedBoolean($this->conditionFactory->false());
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $alwaysTrue);
        self::assertEquals($this->authors, $filteredAuthors);
    }

    public function testPropertiesEqual(): void
    {
        $birthMonthAndDaySimilar = $this->conditionFactory->propertiesEqual(['birth', 'day'], ['birth', 'month']);
        $expected = [$this->authors['salinger']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $birthMonthAndDaySimilar));
        self::assertEquals($expected, $filteredAuthors);
    }

    public function testPropertiesEqualSpecialNullHandling(): void
    {
        $nullEqualityCheck = $this->conditionFactory->propertiesEqual(['pseudonym'], ['birth', 'region']);
        // without `null !== null` handling this would return Tolkien
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $nullEqualityCheck);
        self::assertEquals([], $filteredAuthors);
    }

    public function testPropertyBetweenValuesInclusive(): void
    {
        $born19century = $this->conditionFactory->propertyBetweenValuesInclusive(1800, 1900, ['birth', 'year']);
        $expected = [$this->authors['tolkien'], $this->authors['dickens']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $born19century));
        self::assertEquals($expected, $filteredAuthors);
    }

    public function testPropertyHasAnyOfValues(): void
    {
        $bornJanuaryOrFebruary = $this->conditionFactory->propertyHasAnyOfValues([1, 2], ['birth', 'month']);
        $expected = [$this->authors['tolkien'], $this->authors['dickens'], $this->authors['salinger']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $bornJanuaryOrFebruary));
        self::assertEquals($expected, $filteredAuthors);
    }

    public function testPropertyHasValue(): void
    {
        $propertyHasValue = $this->conditionFactory->propertyHasValue('England', ['author', 'birth', 'country']);
        $expectedBooks = [$this->books['pickwickPapers'], $this->books['philosopherStone'], $this->books['deathlyHallows']];
        $filteredBooks = array_values($this->conditionEvaluator->filterArray($this->books, $propertyHasValue));
        self::assertEquals($expectedBooks, $filteredBooks);
    }

    public function testPropertyHasValueWithNull(): void
    {
        $this->expectException(\TypeError::class);
        $propertyHasValue = $this->conditionFactory->propertyHasValue(null, ['author', 'pseudonym']);
    }

    public function testPropertyHasValueWithNullManually(): void
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, ['author', 'pseudonym']);
        $propertyHasValue = new AllEqual(
            new Property($propertyPath),
            new Value(null)
        );

        $filteredBooks = $this->conditionEvaluator->filterArray($this->books, $propertyHasValue);
        self::assertEquals([], $filteredBooks);
    }

    public function testPropertyIsNull(): void
    {
        $propertyIsNull = $this->conditionFactory->propertyIsNull(['author', 'pseudonym']);
        $expectedBooks = [$this->books['beowulf']];
        $filteredBooks = array_values($this->conditionEvaluator->filterArray($this->books, $propertyIsNull));
        self::assertEquals($expectedBooks, $filteredBooks);
    }

    public function testPropertyHasSize(): void
    {
        $propertyHasSize = $this->conditionFactory->propertyHasSize(2, ['books']);
        $expectedAuthors = [$this->authors['rowling']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $propertyHasSize));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testPropertyHasSizeWithInvalidPathAndPathMerge(): void
    {
        $this->expectError();
        $propertyHasSize = $this->conditionFactory->propertyHasSize(2, ['books', 'title']);
        $this->conditionEvaluator->filterArray($this->authors, $propertyHasSize);
    }

    public function testPropertyHasSizeWithInvalidPath(): void
    {
        $this->expectError();
        $propertyHasSize = $this->conditionFactory->propertyHasSize(2, ['books', 'title']);
        $this->conditionEvaluator->filterArray($this->authors, $propertyHasSize);
    }

    public function testPropertyHasStringContainingCaseInsensitiveValue(): void
    {
        $hasString = $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue('Phen', ['name']);
        $expectedAuthors = [$this->authors['king']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $hasString));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testPropertyHasStringAsMember(): void
    {
        $scifi = $this->conditionFactory->propertyHasStringAsMember('Novel', ['tags']);
        $expectedAuthors = [$this->books['pickwickPapers']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->books, $scifi));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testPropertyHasNotStringAsMember(): void
    {
        $noScifi = $this->conditionFactory->propertyHasNotStringAsMember('Novel', ['tags']);
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->books, $noScifi));
        self::assertNotContains($this->books['pickwickPapers'], $filteredAuthors);
    }

    public function testFunctionResultsEqualWithTwo(): void
    {
        $pathA = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['birth', 'day']));
        $pathB = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['birth', 'month']));
        $birthMonthAndDaySimilar = new AllEqual($pathA, $pathB);
        $expected = [$this->authors['salinger']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $birthMonthAndDaySimilar));
        self::assertEquals($expected, $filteredAuthors);
    }

    public function testFunctionResultsEqualWithThree(): void
    {
        $pathA = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['birth', 'day']));
        $pathB = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['birth', 'month']));
        $pathC = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['birth', 'year']));
        $birthMonthAndDaySimilar = new AllEqual($pathA, $pathB, $pathC);
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $birthMonthAndDaySimilar);
        self::assertEquals([], $filteredAuthors);
    }

    public function testFunctionResultHasValue(): void
    {
        $country = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['author', 'birth', 'country']));
        $england = new Value('England');
        $propertyHasValue = new AllEqual($country, $england);
        $expectedBooks = [$this->books['pickwickPapers'], $this->books['philosopherStone'], $this->books['deathlyHallows']];
        $filteredBooks = array_values($this->conditionEvaluator->filterArray($this->books, $propertyHasValue));
        self::assertEquals($expectedBooks, $filteredBooks);
    }

    public function testFunctionResultHasSize(): void
    {
        $size = new Size(new Property(new PropertyPath(null, '', PropertyPath::DIRECT, ['books'])));
        $two = new Value(2);
        $hasSize = new AllEqual($size, $two);
        $expectedAuthors = [$this->authors['rowling']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $hasSize));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testFunctionResultHasDoubleSizeWithSum(): void
    {
        $size = new Size(new Property(new PropertyPath(null, '', PropertyPath::DIRECT, ['books'])));
        $doubleSize = new Sum($size, $size);
        $two = new Value(4);
        $hasSize = new AllEqual($doubleSize, $two);
        $expectedAuthors = [$this->authors['rowling']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $hasSize));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testFunctionLowerCase(): void
    {
        $constant = new Value('phen');
        $property = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['name']));
        $lower = new LowerCase($property);
        $functionCondition = new StringContains($lower, $constant, false);
        $expectedAuthors = [$this->authors['king']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $functionCondition));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testFunctionLowerCaseFalse(): void
    {
        $constant = new Value('PHEN');
        $property = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['name']));
        $lower = new LowerCase($property);
        $functionCondition = new StringContains($lower, $constant, true);
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $functionCondition);
        self::assertEquals([], $filteredAuthors);
    }

    public function testFunctionUpperCase(): void
    {
        $constant = new Value('PHEN');
        $property = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['name']));
        $upper = new UpperCase($property);
        $functionCondition = new StringContains($upper, $constant, false);
        $expectedAuthors = [$this->authors['king']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $functionCondition));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testFunctionUpperCaseFalse(): void
    {
        $constant = new Value('phen');
        $property = new Property(new PropertyPath(null, '', PropertyPath::UNPACK, ['name']));
        $upper = new UpperCase($property);
        $functionCondition = new StringContains($upper, $constant, true);
        $filteredAuthors = $this->conditionEvaluator->filterArray($this->authors, $functionCondition);
        self::assertEquals([], $filteredAuthors);
    }

    public function testFunctionResultHasDoubleSizeWithProduct(): void
    {
        $size = new Size(new Property(new PropertyPath(null, '', PropertyPath::DIRECT, ['books'])));
        $doubleSize = new Product(new Value(2), $size);
        $two = new Value(4);
        $hasSize = new AllEqual($doubleSize, $two);
        $expectedAuthors = [$this->authors['rowling']];
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $hasSize));
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testCustomMemberCondition(): void
    {
        $propertyPath = new PropertyPath(null, '', PropertyPath::DIRECT, ['books', 'title']);
        $condition = new AllEqual(
            new Value(true),
            new AllEqual(
                new Property($propertyPath),
                new Value('Harry Potter and the Philosopher\'s Stone')
            ),
            new AllEqual(
                new Property($propertyPath),
                new Value('Harry Potter and the Deathly Hallows')
            )
        );
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $condition));
        self::assertEquals([], $filteredAuthors);
    }

    public function testCustomMemberConditionWithSalt(): void
    {
        $propertyPathA = new PropertyPath(null, 'a', PropertyPath::DIRECT, ['books', 'title']);
        $propertyPathB = new PropertyPath(null, 'b', PropertyPath::DIRECT, ['books', 'title']);
        $condition = new AllEqual(
            new Value(true),
            new AllEqual(
                new Property($propertyPathA),
                new Value('Harry Potter and the Philosopher\'s Stone')
            ),
            new AllEqual(
                new Property($propertyPathB),
                new Value('Harry Potter and the Deathly Hallows')
            )
        );
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $condition));
        $expectedAuthors = [$this->authors['rowling']];
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }

    public function testAllValuesPresentInMemberListProperties(): void
    {
        $condition = $this->conditionFactory->allValuesPresentInMemberListProperties([
            'Harry Potter and the Philosopher\'s Stone',
            'Harry Potter and the Deathly Hallows'
        ], ['books', 'title']);
        $filteredAuthors = array_values($this->conditionEvaluator->filterArray($this->authors, $condition));
        $expectedAuthors = [$this->authors['rowling']];
        self::assertEquals($expectedAuthors, $filteredAuthors);
    }
}
