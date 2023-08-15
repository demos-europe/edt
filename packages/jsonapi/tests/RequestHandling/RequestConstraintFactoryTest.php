<?php

declare(strict_types=1);

namespace Tests\RequestHandling;

use EDT\JsonApi\RequestHandling\ExpectedPropertyCollection;
use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class RequestConstraintFactoryTest extends TestCase
{
    public function testEmpty(): void
    {
        $factory = new RequestConstraintFactory();
        $validator = Validation::createValidatorBuilder()->getValidator();
        $expectedProperties = new ExpectedPropertyCollection(
            [],
            [],
            [],
            [],
            [],
            []
        );
        $data = [
            'data' => [
                'id' => '123',
                'type' => 'Foo',
            ],
        ];

        $constraints = $factory->getBodyConstraints('Foo', '123', $expectedProperties);
        $violations = $validator->validate($data, $constraints);
        self::assertCount(0, $violations);
    }
}
