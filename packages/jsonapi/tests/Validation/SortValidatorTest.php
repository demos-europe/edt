<?php

declare(strict_types=1);

namespace Tests\Validation;

use EDT\JsonApi\Validation\SortException;
use EDT\JsonApi\Validation\SortValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class SortValidatorTest extends TestCase
{
    /**
     * @dataProvider getSuccessData()
     */
    public function testSuccess($sortValue): void
    {
        $sortValidator = new SortValidator(Validation::createValidator());
        self::assertSame($sortValue, $sortValidator->validateFormat($sortValue));
    }

    /**
     * @dataProvider getFailureData()
     */
    public function testFailure($sortValue): void
    {
        $this->expectException(SortException::class);
        $sortValidator = new SortValidator(Validation::createValidator());
        $sortValidator->validateFormat($sortValue);
    }

    public static function getSuccessData(): array
    {
        return [
            ['a'],
            ['-a'],
            ['a.b.c'],
            ['a.b.c,-a.b.c'],
            ['abc'],
            ['abc.def'],
            ['abC.dEf'],
            ['-abc'],
            ['abc.def,-xyz'],
            ['-abc,-def,-xyz'],
            ['-abc.def,xyz'],
            ['-abc.def,-xyz'],
        ];
    }

    public static function getFailureData(): array
    {
        return [
            [null], // #0
            ['-'],
            ['-Abc'],
            ['Abc'],
            ['ABc'],
            ['Cba'], // #5
            ['1bc'],
            ['a-b'],
            ['ab-'],
            ['-1bc'],
            ['-ABC'], // #10
            ['ABC'],
            ['-1'],
            ['0'],
            ['-0'],
            ['+1'], // #15
            ['+0'],
            ['1'],
            [-1],
            [0],
            [1], // #20
            ['abc.-abc'],
            ['A'],
            ['-'],
        ];
    }
}
