<?php

declare(strict_types=1);

namespace Tests\Utilities;

use EDT\Parsing\Utilities\DocblockTagResolver;
use EDT\Parsing\Utilities\TypeResolver;
use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
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

    public static function getGetTypeData(): array
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
            [ClassWithProperties::class, [
                // first level:
                SingleTemplateClassForProperty::class.'<string>',
                DoubleTemplateClassForProperty::class.'<string,string>',
                \Tests\Utilities\subnamespace\DoubleTemplateClassForProperty::class.'<string,string>',
                // second level:
                SingleTemplateClassForProperty::class.'<'.\Tests\Utilities\subnamespace\SingleTemplateClassForProperty::class.'<string>>',
                DoubleTemplateClassForProperty::class.'<string,'.\Tests\Utilities\subnamespace\DoubleTemplateClassForProperty::class.'<string,string>>',
                // second level with template parameter:
                // FIXME (#147): generics are not well supported, unknown types will be prefixed with a backslash and handled as string without further trying to resolve it
                SingleTemplateClassForProperty::class.'<'.\Tests\Utilities\subnamespace\SingleTemplateClassForProperty::class.'<\T>>',
                DoubleTemplateClassForProperty::class.'<\T,'.\Tests\Utilities\subnamespace\DoubleTemplateClassForProperty::class.'<\T,\T>>',
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
        for ($i = 0; $i < $templateParameterCount; $i++) {
            $actualTemplateParameter = $actualTemplateParameters[$i] ?? 'n/a';
            $expectedTemplateParameter = $expectedTemplateParameters[$i];
            self::assertSame($expectedTemplateParameter, $actualTemplateParameter);
        }
        self::assertCount($templateParameterCount, $actualTemplateParameters);
    }

    /**
     * @dataProvider getInvalidPropertyTypeTestData
     */
    public function testGetSplitOffTemplateParametersWithInvalidData(string $rawInputString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TypeResolver::getSplitOffTemplateParameters($rawInputString);
    }

    public static function getValidPropertyTypeTestData(): array
    {
        return [
            ['MyClass<Foo>', 'MyClass', ['Foo']],
            ['MyClass<Foo, Bar>', 'MyClass', ['Foo', 'Bar']],
            ['MyClass<Foo, \'bar\'>', 'MyClass', ['Foo', '\'bar\'']],
            ['MyClass', 'MyClass', []],
            ['MyClass_', 'MyClass_', []],
            ['MyClass<  Foo  ,    Bar   >', 'MyClass', ['Foo', 'Bar']],
            ['MyClass<Foo<Bar>>', 'MyClass', ['Foo<Bar>']],
            ['\EDT\TEntity<f>', '\EDT\TEntity', ['f']],
            ['EDT\TEntity<f>', 'EDT\TEntity', ['f']],
            ['EDT\TEntity<f>', 'EDT\TEntity', ['f']],
            ['\TEntity<f>', '\TEntity', ['f']],
            [
                'Foo<string,Bar<string,string>>',
                'Foo',
                ['string', 'Bar<string,string>'],
            ],
            [
                DoubleTemplateClassForProperty::class.'<string,'.\Tests\Utilities\subnamespace\DoubleTemplateClassForProperty::class.'<string,string>>',
                DoubleTemplateClassForProperty::class,
                ['string', \Tests\Utilities\subnamespace\DoubleTemplateClassForProperty::class.'<string,string>'],
            ]
        ];
    }

    public static function getInvalidPropertyTypeTestData(): array
    {
        return [
            ['MyClass<>'],
            ['MyClass<,, Bar>'],
            ['MyClass<Bar,>'],
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
            ['MyClass<>>'],
            ['MyClass<Foo>Bar>'],
            ['MyClass<<>'],
            ['MyClass<Foo<Bar>'],
        ];
    }
}
