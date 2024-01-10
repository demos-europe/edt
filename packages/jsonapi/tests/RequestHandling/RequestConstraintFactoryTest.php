<?php

declare(strict_types=1);

namespace Tests\RequestHandling;

use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class RequestConstraintFactoryTest extends TestCase
{
    /**
     * @param list<non-empty-string> $requiredAttributes list of property names
     * @param array<non-empty-string, non-empty-string> $requiredToOneRelationships
     * @param array<non-empty-string, non-empty-string> $requiredToManyRelationships
     * @param list<non-empty-string> $optionalAttributes list of property names
     * @param array<non-empty-string, non-empty-string> $optionalToOneRelationships
     * @param array<non-empty-string, non-empty-string> $optionalToManyRelationships
     *
     * @dataProvider getEmptyUpdateData
     */
    public function testEmptyUpdate(
        int $expectedViolationsCount,
        array $requiredAttributes,
        array $requiredToOneRelationships,
        array $requiredToManyRelationships,
        array $optionalAttributes,
        array $optionalToOneRelationships,
        array $optionalToManyRelationships
    ): void {
        $factory = new RequestConstraintFactory(1, false);
        $validator = Validation::createValidatorBuilder()->getValidator();
        $expectedProperties = new ExpectedPropertyCollection(
            $requiredAttributes,
            $requiredToOneRelationships,
            $requiredToManyRelationships,
            $optionalAttributes,
            $optionalToOneRelationships,
            $optionalToManyRelationships
        );
        $data = [
            'data' => [
                'id' => '123',
                'type' => 'Foo',
            ],
        ];

        $constraints = $factory->getBodyConstraints('Foo', '123', $expectedProperties);
        $violations = $validator->validate($data, $constraints);
        self::assertCount($expectedViolationsCount, $violations);
    }

    public function getEmptyUpdateData(): array
    {
        return [
            [2, ['a'], ['b' => 'b'], ['c' => 'c'], ['d'], ['e' => 'e'], ['f' => 'f']],
            [1, [], ['b' => 'b'], ['c' => 'c'], ['d'], ['e' => 'e'], ['f' => 'f']],
            [1, [], [], ['c' => 'c'], ['d'], ['e' => 'e'], ['f' => 'f']],
            [0, [], [], [], ['d'], ['e' => 'e'], ['f' => 'f']],
            [0, [], [], [], [], ['e' => 'e'], ['f' => 'f']],
            [0, [], [], [], [], [], ['f' => 'f']],
            [0, [], [], [], [], [], ['f' => 'f']],
            [2, ['a'], ['b' => 'b'], ['c' => 'c'], ['d'], ['e' => 'e'], []],
            [2, ['a'], ['b' => 'b'], ['c' => 'c'], ['d'], [], []],
            [2, ['a'], ['b' => 'b'], ['c' => 'c'], [], [], []],
            [2, ['a'], ['b' => 'b'], [], [], [], []],
            [1, ['a'], [], [], [], [], []],
            [0, [], [], [], [], [], []],
        ];
    }
}
