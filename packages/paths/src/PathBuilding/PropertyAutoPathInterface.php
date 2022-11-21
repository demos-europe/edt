<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use EDT\Querying\Contracts\PropertyPathInterface;
use IteratorAggregate;

interface PropertyAutoPathInterface extends PropertyPathInterface, IteratorAggregate
{
    public const TAG_PROPERTY = 'property';

    public const TAG_PROPERTY_READ = 'property-read';

    /**
     * Usage is discouraged, as accesses will be reading, not writing.
     */
    public const TAG_PROPERTY_WRITE = 'property-write';

    public const TAG_PARAM = 'param';

    public const TAG_VAR = 'var';

    public const SUPPORTED_TARGET_TAGS = [
        self::TAG_PROPERTY,
        self::TAG_PROPERTY_READ,
        self::TAG_PROPERTY_WRITE,
        self::TAG_PARAM,
        self::TAG_VAR,
    ];

    public function setParent(PropertyAutoPathInterface $parent): void;

    /**
     * @param non-empty-string $parentPropertyName
     */
    public function setParentPropertyName(string $parentPropertyName): void;

    /**
     * Returns the instances that are part of this path.
     *
     * @return non-empty-list<PropertyAutoPathInterface> All objects that are part of this path, including the starting object without corresponding
     * property name.
     */
    public function getAsValues(): array;
}
