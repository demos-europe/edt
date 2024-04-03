<?php

declare(strict_types=1);

namespace Tests\RequestHandling;

use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\RequestTransformer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExpectedPropertyCollectionTest extends TestCase
{
    private ExpectedPropertyCollection $propertyCollection;

    /**
     * @see ExpectedPropertyCollection::getConstraintsForAttribute
     */
    private ReflectionMethod $getConstraintsForAttribute;
    private ValidatorInterface $validator;

    /**
     * @see RequestTransformer::splitRelationships()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = Validation::createValidator();

        $this->propertyCollection = new ExpectedPropertyCollection(false, [], [], [], false, [], [], []);

        $this->getConstraintsForAttribute = new ReflectionMethod($this->propertyCollection, 'getConstraintsForAttribute');
        $this->getConstraintsForAttribute->setAccessible(true);
    }

    /**
     * @dataProvider getConstraintsForAttributeData
     */
    public function testGetConstraintsForAttribute(int $expectedViolationsCount, int $validationLevelDepth, bool $allowAnythingBelowDepth, mixed $attributeValue): void
    {
        $constraints = $this->getConstraintsForAttribute->invoke($this->propertyCollection, $validationLevelDepth, $allowAnythingBelowDepth);
        $violations = $this->validator->validate($attributeValue, $constraints);
        self::assertCount($expectedViolationsCount, $violations);
    }

    public function getConstraintsForAttributeData(): array
    {
        return [
            [0, 1, false, ['foo']],
            [0, 1, false, ['foo', 'bar']],
            [0, 1, false, []],
            [0, 0, false, true],
            [0, 0, false, false],
            [0, 0, false, 1],
            [0, 0, false, -1],
            [0, 0, false, 0],
            [0, 0, false, 1.1],
            [0, 0, false, -1.1],
            [0, 0, false, -0.0],
            [0, 0, false, 0.0],
            [0, 1, false, [1]],
            [0, 1, false, [1.1]],
            [0, 1, false, [true]],
            [0, 1, false, [false]],
            [0, 0, false, null],
            [0, 1, false, [null]],
            [0, 1, false, [1, null]],
            [0, 1, false, [null, null]],
            [1, 0, false, [[]]],
            [1, 0, false, [['']]],
            [1, 0, false, [[1]]],
            [1, 0, false, [[null]]],
            [1, 0, false, [[[]]]],
            [1, 0, false, [[['']]]],
            [1, 0, false, [[[1]]]],
            [1, 0, false, [[[null]]]],
            [0, 0, true, [[[[[[1]]]]]]],
                // objects are not allowed, neither at root nor nested
            [1, 1, false, [new \stdClass()]],
            [1, 0, false, new \stdClass()],
        ];
    }
}
