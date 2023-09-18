<?php

declare(strict_types=1);

namespace Tests\Utilities;

use EDT\Parsing\Utilities\DocblockTagParser;
use EDT\Parsing\Utilities\PropertyType;
use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyTag;
use PHPUnit\Framework\TestCase;
use Tests\data\Paths\DummyResource;

class DocblockTagParserTest extends TestCase
{
    public function testGetType(): void
    {
        $parser = new DocblockTagParser(new \ReflectionClass(DummyResource::class));
        $propertyReadTags = $parser->getTags(PropertyTag::PROPERTY_READ->value);
        self::assertCount(10, $propertyReadTags);
        $qualifiedName = $parser->getQualifiedName($propertyReadTags[1]->getType());
        self::assertSame($qualifiedName, End::class);
    }

    /**
     * @dataProvider getValidPropertyTypeTestData
     */
    public function testPropertyType(string $input, array $output): void
    {
        $array = PropertyType::getTemplateParameterStrings($input);
        self::assertEquals($output, $array);
    }
    /**
     * @dataProvider getInvalidPropertyTypeTestData
     */
    public function testInvalidPropertyType(string $input): void
    {
        $this->expectException(\Exception::class);
        PropertyType::getTemplateParameterStrings($input);
    }

    public function getValidPropertyTypeTestData(): array
    {
        return [
            ['MyClass<Foo>', ['Foo']],
            ['MyClass<Foo, Bar>', ['Foo', 'Bar']],
            ['MyClass<Foo, \'bar\'>', ['Foo', '\'bar\'']],
            ['MyClass', []],
            ['MyClass_', []],
            ['MyClass<  Foo  ,    Bar   >', ['Foo', 'Bar']],
            ['MyClass<Foo<Bar>>', ['Foo<Bar>']],
            // not valid as final result, but at least valid in the limited task of the tested method
            ['MyClass<>>', ['>']],
            ['MyClass<Foo>Bar>', ['Foo>Bar']],
            ['MyClass<<>', ['<']],
            ['MyClass<Foo<Bar>', ['Foo<Bar']],
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
