<?php

declare(strict_types=1);

namespace Tests\Querying\Paths;

use EDT\Querying\PropertyPaths\PropertyPath;
use PHPUnit\Framework\TestCase;

class PropertyPathTest extends TestCase
{
    public function testPositiveAccessDepth(): void
    {
        $instance = new PropertyPath(null, '', 1, ['ab', 'cd', 'e']);
        self::assertSame('ab.cd.e(1)', (string)$instance);
    }

    public function testNegativeAccessDepth(): void
    {
        $instance = new PropertyPath(null, '', -21, ['f', 'gh', 'i']);
        self::assertSame('f.gh.i(-21)', (string)$instance);
    }

    public function testComparison(): void
    {
        $instanceA = new PropertyPath(null, '', 1, ['ab', 'cd', 'e']);
        $instanceB = new PropertyPath(null, '', 1, ['ab', 'cd', 'e']);
        self::assertEquals($instanceA, $instanceB);
    }
}
