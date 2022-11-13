<?php

declare(strict_types=1);

namespace Tests\PathBuilding;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PathBuildException;
use EDT\PathBuilding\PropertyAutoPathTrait;
use EDT\Querying\Contracts\PathException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\data\Paths\AmbiguouslyNamedResource;
use Tests\data\Paths\BarResource;
use Tests\data\Paths\BrokenByNestedNsAlias;
use Tests\data\Paths\BrokenByNonNestedNsAlias;
use Tests\data\Paths\nestedNamespace as nested;
use Tests\data\Paths\BrokenResource;
use Tests\data\Paths\DummyResource;
use Tests\data\Paths\FooResource;
use Tests\data\Paths\ImplementationIncompletePath;
use Tests\data\Paths\NonNestedOnlyResource;
use Tests\data\Paths\BrokenByGroupUseStatement;
use Tests\data\Paths\BrokenByUnionTypeProperty;
use Tests\data\Paths\ParamTagged;
use Tests\data\Paths\PropertyTagged;
use Tests\data\Paths\PropertyWriteTagged;
use Tests\data\Paths\VarTagged;
use function get_class;

class PropertyAutoPathTraitTest extends TestCase
{
    public function testAliasNonNestedNs(): void
    {
        $this->expectException(PathBuildException::class);
        $dummyPath = new BrokenByNonNestedNsAlias();
        $dummyPath->aliasedNsNonNested->nonNested;
        self::fail('expected exception');
    }

    public function testAliasNestedNs(): void
    {
        $this->expectException(PathBuildException::class);
        $dummyPath = new BrokenByNestedNsAlias();
        $dummyPath->aliasedNsNested->nested;
        self::fail('expected exception');
    }

    public function testGroupUseDeclaration(): void
    {
        $this->expectException(PathBuildException::class);
        $dummyPath = new BrokenByGroupUseStatement();
        $dummyPath->groupUse->nested;
        self::fail('expected exception');
    }

    public function testFqsenNested(): void
    {
        $dummyPath = new DummyResource();
        $nested = $dummyPath->fqsenNestedResource->nested;
        static::assertSame('fqsenNestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['fqsenNestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));

        $nested = DummyResource::startPath()->fqsenNestedResource->nested;
        static::assertSame('fqsenNestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['fqsenNestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));
    }

    public function testFqsenNonNested(): void
    {
        $dummyPath = new DummyResource();
        $nonNested = $dummyPath->fqsenNonNestedResource->nonNested;
        static::assertSame('fqsenNonNestedResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['fqsenNonNestedResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nonNested));

        $nonNested = DummyResource::startPath()->fqsenNonNestedResource->nonNested;
        static::assertSame('fqsenNonNestedResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['fqsenNonNestedResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nonNested));
    }

    public function testAliasedNonNested(): void
    {
        $dummyPath = new DummyResource();
        $nonNested = $dummyPath->aliasedNonNestedResource->nonNested;
        static::assertSame('aliasedNonNestedResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['aliasedNonNestedResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nonNested));

        $nonNested = DummyResource::startPath()->aliasedNonNestedResource->nonNested;
        static::assertSame('aliasedNonNestedResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['aliasedNonNestedResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nonNested));
    }

    public function testAliasedNested(): void
    {
        $dummyPath = new DummyResource();
        $nested = $dummyPath->aliasedNestedResource->nested;
        static::assertSame('aliasedNestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['aliasedNestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));

        $nested = DummyResource::startPath()->aliasedNestedResource->nested;
        static::assertSame('aliasedNestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['aliasedNestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));
    }

    public function testNested(): void
    {
        $dummyPath = new DummyResource();
        $nested = $dummyPath->nestedResource->nested;
        static::assertSame('nestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['nestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));

        $nested = DummyResource::startPath()->nestedResource->nested;
        static::assertSame('nestedResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['nestedResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\AmbiguouslyNamedResource::class, End::class], $this->toClassNames($nested));

    }

    public function testNestedOnly(): void
    {
        $dummyPath = new DummyResource();
        $nested = $dummyPath->nestedOnlyResource->nested;
        static::assertSame('nestedOnlyResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['nestedOnlyResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\NestedOnlyResource::class, End::class], $this->toClassNames($nested));

        $nested = DummyResource::startPath()->nestedOnlyResource->nested;
        static::assertSame('nestedOnlyResource.nested', $nested->getAsNamesInDotNotation());
        static::assertEquals(['nestedOnlyResource', 'nested'], $nested->getAsNames());
        static::assertEquals([DummyResource::class, nested\NestedOnlyResource::class, End::class], $this->toClassNames($nested));

    }

    public function testUnionTypeNested(): void
    {
        $this->expectException(PathBuildException::class);
        $dummyPath = new BrokenByUnionTypeProperty();
        $dummyPath->unionType->nested;
        self::fail('expected exception');
    }

    public function testUnionTypeNonNested(): void
    {
        $this->expectException(PathBuildException::class);
        $dummyPath = new BrokenByUnionTypeProperty();
        $dummyPath->unionType->nonNested;
        self::fail('expected exception');
    }

    public function testNonNestedOnly(): void
    {
        $dummyPath = new DummyResource();
        $nonNested = $dummyPath->nonNestedOnlyResource->nonNested;
        static::assertSame('nonNestedOnlyResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['nonNestedOnlyResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, NonNestedOnlyResource::class, End::class], $this->toClassNames($nonNested));

        $nonNested = DummyResource::startPath()->nonNestedOnlyResource->nonNested;
        static::assertSame('nonNestedOnlyResource.nonNested', $nonNested->getAsNamesInDotNotation());
        static::assertEquals(['nonNestedOnlyResource', 'nonNested'], $nonNested->getAsNames());
        static::assertEquals([DummyResource::class, NonNestedOnlyResource::class, End::class], $this->toClassNames($nonNested));
    }

    public function testRelationships(): void
    {
        $dummyPath = new DummyResource();

        $fooBar = $dummyPath->foo->bar;
        static::assertSame('foo.bar', $fooBar->getAsNamesInDotNotation());
        static::assertEquals(['foo', 'bar'], $fooBar->getAsNames());
        static::assertEquals([DummyResource::class, FooResource::class, BarResource::class], $this->toClassNames($fooBar));

        $fooBar = DummyResource::startPath()->foo->bar;
        static::assertSame('foo.bar', $fooBar->getAsNamesInDotNotation());
        static::assertEquals(['foo', 'bar'], $fooBar->getAsNames());
        static::assertEquals([DummyResource::class, FooResource::class, BarResource::class], $this->toClassNames($fooBar));

        $fooPath = new FooResource();

        $bar = $fooPath->bar;
        static::assertSame('bar', $bar->getAsNamesInDotNotation());
        static::assertEquals(['bar'], $bar->getAsNames());
        static::assertEquals([FooResource::class, BarResource::class], $this->toClassNames($bar));

        $bar = FooResource::startPath()->bar;
        static::assertSame('bar', $bar->getAsNamesInDotNotation());
        static::assertEquals(['bar'], $bar->getAsNames());
        static::assertEquals([FooResource::class, BarResource::class], $this->toClassNames($bar));

    }

    public function testAttributes(): void
    {
        $dummyPath = new DummyResource();

        $dummyId = $dummyPath->id;
        static::assertSame('id', $dummyId->getAsNamesInDotNotation());
        static::assertEquals(['id'], $dummyId->getAsNames());
        static::assertEquals([DummyResource::class, End::class], $this->toClassNames($dummyId));

        $path2 = $dummyPath->bar->title;
        static::assertSame('bar.title', $path2->getAsNamesInDotNotation());
        static::assertEquals(['bar', 'title'], $path2->getAsNames());
        static::assertEquals([DummyResource::class, BarResource::class, End::class], $this->toClassNames($path2));

        $path3 = $dummyPath->title;
        static::assertSame('title', $path3->getAsNamesInDotNotation());
        static::assertEquals(['title'], $path3->getAsNames());
        static::assertEquals([DummyResource::class, End::class], $this->toClassNames($path3));

        $dummyId = DummyResource::startPath()->id;
        static::assertSame('id', $dummyId->getAsNamesInDotNotation());
        static::assertEquals(['id'], $dummyId->getAsNames());
        static::assertEquals([DummyResource::class, End::class], $this->toClassNames($dummyId));

        $path2 = DummyResource::startPath()->bar->title;
        static::assertSame('bar.title', $path2->getAsNamesInDotNotation());
        static::assertEquals(['bar', 'title'], $path2->getAsNames());
        static::assertEquals([DummyResource::class, BarResource::class, End::class], $this->toClassNames($path2));

        $path3 = DummyResource::startPath()->title;
        static::assertSame('title', $path3->getAsNamesInDotNotation());
        static::assertEquals(['title'], $path3->getAsNames());
        static::assertEquals([DummyResource::class, End::class], $this->toClassNames($path3));

        $fooPath = new FooResource();

        $path5 = $fooPath->bar->title;
        static::assertSame('bar.title', $path5->getAsNamesInDotNotation());
        static::assertEquals(['bar', 'title'], $path5->getAsNames());
        static::assertEquals([FooResource::class, BarResource::class, End::class], $this->toClassNames($path5));

        $path6 = $fooPath->barTitle;
        static::assertSame('barTitle', $path6->getAsNamesInDotNotation());
        static::assertEquals(['barTitle'], $path6->getAsNames());
        static::assertEquals([FooResource::class, End::class], $this->toClassNames($path6));

        $path5 = FooResource::startPath()->bar->title;
        static::assertSame('bar.title', $path5->getAsNamesInDotNotation());
        static::assertEquals(['bar', 'title'], $path5->getAsNames());
        static::assertEquals([FooResource::class, BarResource::class, End::class], $this->toClassNames($path5));

        $path6 = FooResource::startPath()->barTitle;
        static::assertSame('barTitle', $path6->getAsNamesInDotNotation());
        static::assertEquals(['barTitle'], $path6->getAsNames());
        static::assertEquals([FooResource::class, End::class], $this->toClassNames($path6));

    }

    public function testCircle(): void
    {
        $fooPath = new FooResource();

        $path8 = $fooPath->foo->foo->bar->foo->bar->foo->foo;
        static::assertSame('foo.foo.bar.foo.bar.foo.foo', $path8->getAsNamesInDotNotation());
        static::assertEquals(['foo', 'foo', 'bar', 'foo', 'bar', 'foo', 'foo'], $path8->getAsNames());
        static::assertEquals([FooResource::class, FooResource::class, FooResource::class, BarResource::class, FooResource::class, BarResource::class, FooResource::class, FooResource::class], $this->toClassNames($path8));

        $path8 = FooResource::startPath()->foo->foo->bar->foo->bar->foo->foo;
        static::assertSame('foo.foo.bar.foo.bar.foo.foo', $path8->getAsNamesInDotNotation());
        static::assertEquals(['foo', 'foo', 'bar', 'foo', 'bar', 'foo', 'foo'], $path8->getAsNames());
        static::assertEquals([FooResource::class, FooResource::class, FooResource::class, BarResource::class, FooResource::class, BarResource::class, FooResource::class, FooResource::class], $this->toClassNames($path8));
    }

    public function testBrokenPathParsing(): void
    {
        $this->expectException(PathBuildException::class);
        $brokenType = new BrokenResource();
        $brokenType->id;
        static::fail('expected exception');
    }

    /**
     * If the path class does not specify a docblock it must be handled as if the docblock would be empty.
     * @dataProvider getIncompleteImplementation()
     */
    public function testIncompleteImplementationPathDots(ImplementationIncompletePath $path): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Access to path implementation `Tests\data\Paths\ImplementationIncompletePath` resulted in a path without any segments. Make sure to only pass paths with at least one segment around.');
        $path->getAsNamesInDotNotation();
    }

    /**
     * If the path class does not specify a docblock it must be handled as if the docblock would be empty.
     * @dataProvider getIncompleteImplementation()
     */
    public function testIncompleteImplementationPathNames(ImplementationIncompletePath $path): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Access to path implementation `Tests\data\Paths\ImplementationIncompletePath` resulted in a path without any segments. Make sure to only pass paths with at least one segment around.');
        $path->getAsNames();
    }

    /**
     * If the path class does not specify a docblock it must be handled as if the docblock would be empty.
     * @dataProvider getIncompleteImplementation()
     */
    public function testIncompleteImplementationPathClassNames(ImplementationIncompletePath $path): void
    {
        static::assertEquals([ImplementationIncompletePath::class], $this->toClassNames($path));
    }

    public function getIncompleteImplementation(): array
    {
        return [
            [new ImplementationIncompletePath()],
            [ImplementationIncompletePath::startPath()],
        ];
    }

    public function testNonPathPropertyReadTag(): void
    {
        $this->expectException(PathBuildException::class);
        $path = new BarResource('');
        $path->unavailableWithTrait;
        self::fail('Expected exception');
    }

    public function testPropertyReadsFoo(): void
    {
        $foo = new FooResource();
        $reflector = new ReflectionClass($foo);
        $method = $reflector->getMethod('getAutoPathProperties');
        $method->setAccessible(true);
        $names = $method->invokeArgs($foo, []);
        $expected = [
            'id' => 'EDT\PathBuilding\End',
            'barTitle' => '\EDT\PathBuilding\End',
            'bar' => 'Tests\data\Paths\BarResource',
            'foo' => 'Tests\data\Paths\FooResource',
        ];
        self::assertEquals($expected, $names);

        $foo = FooResource::startPath();
        $reflector = new ReflectionClass($foo);
        $method = $reflector->getMethod('getAutoPathProperties');
        $method->setAccessible(true);
        $names = $method->invokeArgs($foo, []);
        $expected = [
            'id' => 'EDT\PathBuilding\End',
            'barTitle' => '\EDT\PathBuilding\End',
            'bar' => 'Tests\data\Paths\BarResource',
            'foo' => 'Tests\data\Paths\FooResource',
        ];
        self::assertEquals($expected, $names);
    }

    public function testPropertyReadsBar(): void
    {
        $bar = new BarResource('');
        $reflector = new ReflectionClass($bar);
        $method = $reflector->getMethod('getAutoPathProperties');
        $method->setAccessible(true);
        $names = $method->invokeArgs($bar, []);
        $expected = [
            'foo' => 'Tests\data\Paths\FooResource',
            'title' => '\EDT\PathBuilding\End',
        ];
        self::assertEquals($expected, $names);

        $bar = BarResource::startPath();
        $reflector = new ReflectionClass($bar);
        $method = $reflector->getMethod('getAutoPathProperties');
        $method->setAccessible(true);
        $names = $method->invokeArgs($bar, []);
        $expected = [
            'foo' => 'Tests\data\Paths\FooResource',
            'title' => '\EDT\PathBuilding\End',
        ];
        self::assertEquals($expected, $names);
    }

    public function testPropertyReadsArrayUsageFoo(): void
    {
        $foo = new FooResource();
        self::assertSame('bar', $foo->bar->getAsNamesInDotNotation());
        self::assertEquals(['bar|' => 1], [(string)$foo->bar => 1]);
        self::assertEquals(['bar|' => 1], [$foo->bar->__toString() => 1]);
        self::assertEquals(['bar' => 1], [$foo->bar->getAsNamesInDotNotation() => 1]);

        $foo = FooResource::startPath();
        self::assertSame('bar', $foo->bar->getAsNamesInDotNotation());
        self::assertEquals(['bar|' => 1], [(string)$foo->bar => 1]);
        self::assertEquals(['bar|' => 1], [$foo->bar->__toString() => 1]);
        self::assertEquals(['bar' => 1], [$foo->bar->getAsNamesInDotNotation() => 1]);
    }

    public function testPropertyNameToStringException(): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Access to path implementation `Tests\data\Paths\BarResource` resulted in a path without any segments. Make sure to only pass paths with at least one segment around.');
        $bar = new BarResource('hello');
        (string)$bar;
    }

    public function testSpecialPropertyNameToStringException(): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Access to path implementation `Tests\data\Paths\BarResource` resulted in a path without any segments. Make sure to only pass paths with at least one segment around.');
        $bar = BarResource::startPath('!');
        (string)$bar;
    }

    public function testEmptyPropertyNameToStringException(): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Access to path implementation `Tests\data\Paths\BarResource` resulted in a path without any segments. Make sure to only pass paths with at least one segment around.');
        $bar = BarResource::startPath();
        (string)$bar;
    }

    public function testPropertyReadsArrayUsageBar(): void
    {
        $bar = new BarResource('hello');
        self::assertSame('foo', $bar->foo->getAsNamesInDotNotation());
        self::assertEquals(['foo' => 1], [$bar->foo->getAsNamesInDotNotation() => 1]);

        $bar = BarResource::startPath('world');
        self::assertSame('foo', $bar->foo->getAsNamesInDotNotation());
        self::assertEquals(['foo' => 1], [$bar->foo->getAsNamesInDotNotation() => 1]);

        $bar = BarResource::startPath('!');
        self::assertSame('foo', $bar->foo->getAsNamesInDotNotation());
        self::assertEquals(['foo' => 1], [$bar->foo->getAsNamesInDotNotation() => 1]);

        $bar = BarResource::startPath();
        self::assertSame('foo', $bar->foo->getAsNamesInDotNotation());
        self::assertEquals(['foo' => 1], [$bar->foo->getAsNamesInDotNotation() => 1]);
    }

    public function testPropertyTagged(): void
    {
        $path = new PropertyTagged();
        self::assertInstanceOf(End::class, $path->propertyAttribute);
        self::assertInstanceOf(FooResource::class, $path->propertyRelationship);
    }

    public function testVarTagged(): void
    {
        $path = new VarTagged();
        self::assertInstanceOf(End::class, $path->varAttribute);
        self::assertInstanceOf(FooResource::class, $path->varRelationship);
    }

    public function testPropertyWriteTagged(): void
    {
        $path = new PropertyWriteTagged();
        self::assertInstanceOf(End::class, $path->propertyWriteAttribute);
        self::assertInstanceOf(FooResource::class, $path->propertyWriteRelationship);
    }

    public function testParamTagged(): void
    {
        $path = new ParamTagged();
        self::assertInstanceOf(End::class, $path->paramAttribute);
        self::assertInstanceOf(FooResource::class, $path->paramRelationship);
    }

    public function testAliasing(): void
    {
        $foo = new FooResource();
        $aliases = $foo->getAliases();
        self::assertEquals([
            'barTitle' => ['bar', 'title'],
        ], $aliases);
        $path = new ParamTagged();
        self::assertInstanceOf(End::class, $path->paramAttribute);
        self::assertInstanceOf(FooResource::class, $path->paramRelationship);
    }

    /**
     * @param PropertyAutoPathTrait&object $autoPath
     * @return non-empty-list<class-string>
     */
    private function toClassNames($autoPath): array
    {
        return array_map(
            static fn (object $object): string => get_class($object),
            $autoPath->getAsValues()
        );
    }

    public function testEndUnpack(): void
    {
        $dummyPath = new DummyResource();
        $title = $dummyPath->title;
        $this->unpack(...$title);
    }

    protected function unpack(string ...$path): void
    {
        self::assertEquals(['title'], $path);
    }
}
