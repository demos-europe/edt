<?php

declare(strict_types=1);

namespace Tests\Schema;

use EDT\JsonApi\Schema\ResourceIdentifierObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierObjectTest extends TestCase
{
    public function testGetter(): void
    {
        $resourceIdentifierObject = new ResourceIdentifierObject(['id' => 'foo', 'type' => 'bar']);
        self::assertSame('foo', $resourceIdentifierObject->getId());
        self::assertSame('bar', $resourceIdentifierObject->getType());
    }
}
