<?php

declare(strict_types=1);

namespace Tests\Utilities;

use EDT\Parsing\Utilities\DocblockTagResolver;
use EDT\Parsing\Utilities\TypeResolver;
use EDT\Parsing\Utilities\ClassOrInterfaceType;
use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyTag;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\data\Paths\AmbiguouslyNamedResource;
use Tests\data\Paths\BarResource;
use Tests\data\Paths\DummyResource;
use Tests\data\Paths\nestedNamespace\NestedOnlyResource;
use Tests\data\Paths\NonNestedOnlyResource;

class DocblockTagParserTest extends TestCase
{
    /**
     * @dataProvider getGetTypeData
     */
    public function testGetType(string $class, array $expectedTypes): void
    {
        $reflectionClass = new ReflectionClass($class);
        $tagResolver = new DocblockTagResolver($reflectionClass);
        $typeResolver = new TypeResolver($reflectionClass);
        $propertyReadTags = $tagResolver->getTags(PropertyTag::PROPERTY_READ->value);
        $expectedCount = count($expectedTypes);
        self::assertCount($expectedCount, $propertyReadTags);
        for ($i = 0; $i < $expectedCount; $i++) {
            $type = ClassOrInterfaceType::fromType($propertyReadTags[$i]->getType(), $typeResolver);
            $actual = ltrim($type->getFullString(false), '\\');
            $actual = str_replace('"', "'", $actual);
            $expected = ltrim($expectedTypes[$i], '\\');
            self::assertSame($expected, $actual, "Expected `$expected`, got `$actual` for i=$i.");
        }
    }

    public function getGetTypeData(): array
    {
        return [
            [DummyResource::class, [
                End::class,
                End::class,
                BarResource::class,
                AmbiguouslyNamedResource::class,
                \Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource::class,
                \Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource::class,
                AmbiguouslyNamedResource::class,
                \Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource::class,
                NonNestedOnlyResource::class,
                NestedOnlyResource::class,
            ]],
        ];
    }

    /**
     * @dataProvider getValidPropertyTypeTestData
     */
    public function testGetSplitOffTemplateParametersWithValidData(string $rawInputString, string $expectedClassName, array $expectedTemplateParameters): void
    {
        [$actualClassName, $actualTemplateParameters] = TypeResolver::getSplitOffTemplateParameters($rawInputString);
        self::assertSame($expectedClassName, $actualClassName);
        $templateParameterCount = count($expectedTemplateParameters);
        self::assertCount($templateParameterCount, $actualTemplateParameters);
        for ($i = 0; $i < $templateParameterCount; $i++) {
            $actualTemplateParameter = $actualTemplateParameters[$i];
            $expectedTemplateParameter = $expectedTemplateParameters[$i];
            self::assertSame($expectedTemplateParameter, $actualTemplateParameter);
        }
    }

    /**
     * @dataProvider getInvalidPropertyTypeTestData
     */
    public function testGetSplitOffTemplateParametersWithInvalidData(string $rawInputString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TypeResolver::getSplitOffTemplateParameters($rawInputString);
    }

    public function getValidPropertyTypeTestData(): array
    {
        return [
            ['MyClass<Foo>', 'MyClass', ['Foo']],
            ['MyClass<Foo, Bar>', 'MyClass', ['Foo', 'Bar']],
            ['MyClass<Foo, \'bar\'>', 'MyClass', ['Foo', '\'bar\'']],
            ['MyClass', 'MyClass', []],
            ['MyClass_', 'MyClass_', []],
            ['MyClass<  Foo  ,    Bar   >', 'MyClass', ['Foo', 'Bar']],
            ['MyClass<Foo<Bar>>', 'MyClass', ['Foo<Bar>']],
            // not valid as final result, but at least valid in the limited task of the tested method
            ['MyClass<>>', 'MyClass', ['>']],
            ['MyClass<Foo>Bar>', 'MyClass', ['Foo>Bar']],
            ['MyClass<<>', 'MyClass', ['<']],
            ['MyClass<Foo<Bar>', 'MyClass', ['Foo<Bar']],
        ];
    }

    public function getInvalidPropertyTypeTestData(): array
    {
        return [
            ['MyClass<>'],
            ['MyClass<,, Bar>'],
            ['MyClass<,>'],
            ['MyClass<<'],
            ['MyClass>>'],
            ['MyClass!'],
            ['MyClass.'],
            ['My Class'],
            ['>'],
            ['<'],
            ['<>'],
            ['!>'],
            ['!>'],
            ['>!'],
            ['<!'],
        ];
    }
}
