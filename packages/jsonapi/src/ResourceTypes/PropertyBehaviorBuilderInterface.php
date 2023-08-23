<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

/**
 * @template TSetability
 */
interface PropertyBehaviorBuilderInterface
{
    /**
     * @param non-empty-string $propertyName
     * @param non-empty-list<non-empty-string> $propertyPath
     *
     * @return TSetability
     */
    public function build(string $propertyName, array $propertyPath);
}
