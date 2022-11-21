<?php

declare(strict_types=1);

namespace EDT\PathBuilding\SegmentFactories;

use EDT\PathBuilding\PropertyAutoPathInterface;
use EDT\Querying\Contracts\PropertyPathInterface;
use Exception;

interface SegmentFactoryInterface
{
    /**
     * @param class-string<PropertyAutoPathInterface> $className
     * @param PropertyAutoPathInterface $parent
     * @param non-empty-string $parentPropertyName
     *
     * @throws Exception
     */
    public function createNextSegment(string $className, PropertyAutoPathInterface $parent, string $parentPropertyName): PropertyPathInterface;
}
