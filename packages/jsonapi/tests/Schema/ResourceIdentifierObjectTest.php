<?php

declare(strict_types=1);

namespace Tests\Schema;

use EDT\JsonApi\Schema\ResourceIdentifierObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierObjectTest extends TestCase
{
    public function testInvalidInitializeWithMissingId()
    {
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifierObject(['type' => 'foo']);
        self::fail('expected exception');
    }

    public function testInvalidInitializeWithInvalidId()
    {
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifierObject(['id' => 42, 'type' => 'foo']);
        self::fail('expected exception');
    }

    public function testInvalidInitializeWithMissingType()
    {
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifierObject(['id' => 'foo']);
        self::fail('expected exception');
    }

    public function testInvalidInitializeWithInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        new ResourceIdentifierObject(['id' => 'foo', 'type' => 42]);
        self::fail('expected exception');
    }

    public function testGetter()
    {
        $resourceIdentifierObject = new ResourceIdentifierObject(['id' => 'foo', 'type' => 'bar']);
        self::assertSame('foo', $resourceIdentifierObject->getId());
        self::assertSame('bar', $resourceIdentifierObject->getType());
    }
}
