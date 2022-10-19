<?php

declare(strict_types=1);

namespace Tests\Querying\ConditionParsers;

use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\ConditionParsers\Drupal\DrupalConditionParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterException;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\Contracts\ConditionFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use TypeError;

class DrupalConditionFactoryTest extends TestCase
{
    /**
     * @var ConditionFactoryInterface
     */
    private $conditionFactory;
    /**
     * @var DrupalFilterParser
     */
    private $filterFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conditionFactory = new PhpConditionFactory();
        $drupalConditionFactory = new PredefinedDrupalConditionFactory($this->conditionFactory);
        $conditionParser = new DrupalConditionParser($drupalConditionFactory);
        $this->filterFactory = new DrupalFilterParser(
            $this->conditionFactory,
            $conditionParser,
            new DrupalFilterValidator(
                Validation::createValidator(),
                $drupalConditionFactory
            )
        );
    }

    public function testTwoExplicitEqualsOrExplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'operator' => '=',
                    'path' => 'id',
                    'value' => 'ABC',
                    'memberOf' => 'group_or',
                ],
            ],
            'condition_b' => [
                'condition' => [
                    'operator' => '=',
                    'path' => 'id',
                    'value' => 'DEF',
                    'memberOf' => 'group_or',
                ],
            ],
            'group_or' => [
                'group' => [
                    'conjunction' => 'OR',
                ],
            ],
        ]);

        $expected = $this->conditionFactory->anyConditionApplies(
            $this->conditionFactory->propertyHasValue('ABC', 'id'),
            $this->conditionFactory->propertyHasValue('DEF', 'id')
        );

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testTwoEqualsImplicitOneEqualsExplicitAndImplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'operator' => '=',
                    'path' => 'book.authorName',
                    'value' => 'Stephen King',
                ],
            ],
            'condition_b' => [
                'condition' => [
                    'path' => 'book.editorName',
                    'value' => 'Stephen King',
                ],
            ],
            'condition_c' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 123,
                ],
            ],
        ]);

        $expected = [
            $this->conditionFactory->propertyHasValue('Stephen King', 'book', 'authorName'),
            $this->conditionFactory->propertyHasValue('Stephen King', 'book', 'editorName'),
            $this->conditionFactory->propertyHasValue(123, 'id'),
        ];

        self::assertSameSize($expected, $actual);
        self::assertSame((string)$expected[0], (string)$actual[0]);
        self::assertSame((string)$expected[1], (string)$actual[1]);
        self::assertSame((string)$expected[2], (string)$actual[2]);
    }

    public function testTwoEqualsImplicitAndExplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'book.id',
                    'value' => 234,
                ],
            ],
            'condition_b' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 567,
                ],
            ],
        ]);

        $expected = [
            $this->conditionFactory->propertyHasValue(234, 'book', 'id'),
            $this->conditionFactory->propertyHasValue(567, 'id'),
        ];

        self::assertSameSize($expected, $actual);
        self::assertSame((string)$expected[0], (string)$actual[0]);
        self::assertSame((string)$expected[1], (string)$actual[1]);
    }

    public function testOneEqualsImplicitOneEqualsImplicitAndImplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'book.author.id',
                    'value' => 987,
                ],
            ],
            'condition_b' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 654,
                ],
            ],
        ]);

        $expected = [
            $this->conditionFactory->propertyHasValue(987, 'book', 'author', 'id'),
            $this->conditionFactory->propertyHasValue(654, 'id'),
        ];

        self::assertSameSize($expected, $actual);
        self::assertSame((string)$expected[0], (string)$actual[0]);
        self::assertSame((string)$expected[1], (string)$actual[1]);
    }

    public function testOneEqualImplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'author.id',
                    'value' => 680,
                ],
            ],
        ]);

        $expected = $this->conditionFactory->propertyHasValue(680, 'author', 'id');

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testEqualsImplicitRecursivePath(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'author.books.author.books.author.books.author',
                    'value' => 123,
                ],
            ],
        ]);

        $expected = $this->conditionFactory->propertyHasValue(123, 'author', 'books', 'author', 'books', 'author', 'books', 'author');

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testOneBetweenOneEqualsImplicitAndImplicit(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'operator' => 'BETWEEN',
                    'path' => 'author.booksWritten',
                    'value' => [-2, 7],
                ],
            ],
            'condition_b' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 531,
                ],
            ],
        ]);

        $expected = [
            $this->conditionFactory->propertyBetweenValuesInclusive(-2, 7, 'author', 'booksWritten'),
            $this->conditionFactory->propertyHasValue(531, 'id'),
        ];

        self::assertSameSize($expected, $actual);
        self::assertSame((string)$expected[0], (string)$actual[0]);
        self::assertSame((string)$expected[1], (string)$actual[1]);
    }

    public function testNestedGroupLine(): void
    {
        $actual = $this->filterFactory->parseFilter(
            [
                'condition' => [
                    'condition' => [
                        'path' => 'book.author.name',
                        'value' => 'Stephen King',
                        'memberOf' => 'group_a',
                    ],
                ],
                'group_b' => [
                    'group' => [
                        'conjunction' => 'AND',
                        'memberOf' => 'group_c',
                    ],
                ],
                'group_d' => [
                    'group' => [
                        'conjunction' => 'AND',
                    ],
                ],
                'group_a' => [
                    'group' => [
                        'conjunction' => 'AND',
                        'memberOf' => 'group_b',
                    ],
                ],
                'group_c' => [
                    'group' => [
                        'conjunction' => 'AND',
                        'memberOf' => 'group_d',
                    ],
                ],
            ]
        );

        $expected = $this->conditionFactory->propertyHasValue('Stephen King', 'book', 'author', 'name');

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testNestedGroups(): void
    {
        $actual = $this->filterFactory->parseFilter(
            [
                'condition_a' => [
                    'condition' => [
                        'path' => 'book.author.name',
                        'value' => 'Stephen King',
                        'memberOf' => 'group_and',
                    ],
                ],
                'condition_b' => [
                    'condition' => [
                        'path' => 'book.editor.name',
                        'value' => 'Unknown',
                        'memberOf' => 'group_and',
                    ],
                ],
                'group_and' => [
                    'group' => [
                        'conjunction' => 'AND',
                        'memberOf' => 'group_or',
                    ],
                ],
                'condition_c' => [
                    'condition' => [
                        'path' => 'id',
                        'value' => 123,
                        'memberOf' => 'group_and',
                    ],
                ],
                'condition_d' => [
                    'condition' => [
                        'path' => 'id',
                        'value' => 234,
                        'memberOf' => 'group_or',
                    ],
                ],
                'group_or' => [
                    'group' => [
                        'conjunction' => 'OR',
                    ],
                ],
            ]
        );

        $expected = $this->conditionFactory->anyConditionApplies(
            $this->conditionFactory->propertyHasValue(234, 'id'),
            $this->conditionFactory->allConditionsApply(
                $this->conditionFactory->propertyHasValue('Stephen King', 'book', 'author', 'name'),
                $this->conditionFactory->propertyHasValue('Unknown', 'book', 'editor', 'name'),
                $this->conditionFactory->propertyHasValue(123, 'id')
            )
        );

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testNotEquals(): void
    {
        $actual = $this->filterFactory->parseFilter(
            [
                'condition_a' => [
                    'condition' => [
                        'operator' => '<>',
                        'path' => 'id',
                        'value' => 123,
                    ],
                ],
            ]
        );

        $expected = $this->conditionFactory->propertyHasNotValue(123, 'id');

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testSkippedCondition(): void
    {
        $actual = $this->filterFactory->parseFilter(
            [
                'condition_a' => [
                    'condition' => [
                        'operator' => '<>',
                        'path' => 'id',
                        'value' => 123,
                        'memberOf' => 'non_existing',
                    ],
                ],
            ]
        );

        self::assertSame([], $actual);
    }

    public function testNeitherConditionNorGroup(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'non_condition' => [
                'invalid' => [
                    'path' => 'id',
                    'value' => 123,
                ]
            ]
        ]);
    }

    public function testRootKeyUsed(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            '@root' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 123,
                ],
            ],
        ]);
    }

    public function testGroupNoArray(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter(
            // passing an invalid paramater on purpose
            /** @phpstan-ignore-next-line */
            [
                'group_a' => [
                    'group' => '',
                ],
            ]
        );
    }


    public function testConditionNoArray(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter(
            // passing an invalid paramater on purpose
            /** @phpstan-ignore-next-line */
            [
                'condition_a' => [
                    'condition' => '',
                ],
            ]
        );
    }

    public function testNoCondition(): void
    {
        $actual = $this->filterFactory->parseFilter([]);
        self::assertSame([], $actual);
    }

    public function testUnknownGroupFieldWithoutConditions(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'invalid' => '',
                ],
            ],
        ]);
    }

    public function testUnknownGroupField(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 123,
                    'memberOf' => 'group_a',
                ]
            ],
            'group_a' => [
                'group' => [
                    'invalid' => '',
                ],
            ],
        ]);
    }

    public function testInvalidMemberOfType(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 123,
                    'memberOf' => 1,
                ]
            ],
        ]);
    }

    public function testInvalidMemberOfRoot(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'id',
                    'value' => 123,
                    'memberOf' => '@root',
                ]
            ],
        ]);
    }


    public function testInvalidOrConjunction(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'conjunction' => 'or',
                ],
            ],
        ]);
    }

    public function testInvalidAndConjunction(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'conjunction' => 'and',
                ],
            ],
        ]);
    }

    public function testInvalidConjunctionTypeInt(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'conjunction' => 1,
                ],
            ],
        ]);
    }

    public function testInvalidConditionKey(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'invalid' => '',
                ],
            ],
        ]);
    }

    public function testNoPath(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                ],
            ],
        ]);
    }

    public function testInvalidPathType(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 2
                ],
            ],
        ]);
    }

    public function testNullValue(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'a',
                    'value' => null,
                ],
            ],
        ]);
    }

    public function testEmptyPath(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => '',
                ],
            ],
        ]);
    }

    public function testUnknownOperator(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'condition_a' => [
                'condition' => [
                    'path' => 'a.b',
                    'operator' => 'foo',
                ],
            ],
        ]);
    }

    public function testSingleOrCondition(): void
    {
        $actual = $this->filterFactory->parseFilter([
            'myCondition' => [
                'condition' => [
                    'path' => 'some.path',
                    'memberOf' => 'myGroup',
                    'value' => 'someValue',
                ],
            ],
            'myGroup' => [
                'group' => [
                    'conjunction' => 'OR',
                ],
            ],
        ]);

        $expected = $this->conditionFactory->propertyHasValue('someValue', 'some', 'path');

        self::assertCount(1, $actual);
        self::assertSame((string)$expected, (string)$actual[0]);
    }

    public function testLoop(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'myCondition' => [
                'condition' => [
                    'path' => 'some.path',
                    'memberOf' => 'myGroup',
                    'value' => 'someValue',
                ],
            ],
            'myGroup' => [
                'group' => [
                    'conjunction' => 'OR',
                    'memberOf' => 'myOtherGroup'
                ],
            ],
            'myOtherGroup' => [
                'group' => [
                    'conjunction' => 'OR',
                    'memberOf' => 'myOtherOtherGroup',
                ],
            ],
            'myOtherOtherGroup' => [
                'group' => [
                    'conjunction' => 'OR',
                    'memberOf' => 'myGroup',
                ],
            ],
        ]);
    }

    public function testInvalidGroupField(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'foobar' => 'OR',
                ],
            ],
        ]);
        self::fail('expected an exception');
    }

    public function testConjunctionMissing(): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [],
            ],
        ]);
        self::fail('expected an exception');
    }

    public function testInvalidConjunctionTypeNull(): void
    {
        $this->expectException(TypeError::class);
        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'conjunction' => null,
                ],
            ],
        ]);
        self::fail('expected an exception');
    }

    public function testInvalidConjunctionValue(): void
    {
        $this->expectException(DrupalFilterException::class);

        $this->filterFactory->parseFilter([
            'group_a' => [
                'group' => [
                    'conjunction' => 'FOO',
                ],
            ],
        ]);
        self::fail('expected an exception');
    }

    /**
     * @dataProvider getInvalidFilterNames()
     */
    public function testInvalidFilterNames($filterName): void
    {
        $this->expectException(DrupalFilterException::class);
        $this->filterFactory->parseFilter([
            $filterName => [
                'condition' => [
                    'value' => 0,
                    'path'  => 'foobar'
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getValidFilterNames()
     */
    public function testValidFilterNames($filterName): void
    {
        $conditions = $this->filterFactory->parseFilter([
            $filterName => [
                'condition' => [
                    'value' => 0,
                    'path'  => 'foobar'
                ],
            ],
        ]);
        self::assertCount(1, $conditions);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getValidFilterNames(): array
    {
        return array_map(static function (string $name): array {
            return [$name];
        }, [
            'x',
            'condition',
            'group',
            'a-a-a',
            'a-',
            'FoBAr',
            'fOObAr',
            'ROOT',
            'root',
            '----',
            '-a-',
            '-'
        ]);
    }

    /**
     * @return list<string|int>
     */
    public function getInvalidFilterNames(): array
    {
        return array_map(static function (string $name): array {
            return [$name];
        }, [
            '',
            DrupalFilterParser::ROOT,
            '@ROOT',
            '*',
            'a*',
            'a*a',
            '*a',
            '*a*',
            '^',
            ' ',
            'a ',
            'a a',
            ' a',
            ' a ',
            '1',
            '1234',
            '-1',
            '0',
            1,
            -1,
            0,
        ]);
    }
}
