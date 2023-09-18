<?php

declare(strict_types=1);

namespace EDT\JsonApi\Utilities;

/**
 * @template TBehavior of object
 */
interface PropertyBehaviorBuilderInterface
{
    /**
     * @param non-empty-string $propertyName
     * @param non-empty-list<non-empty-string> $propertyPath
     *
     * @return TBehavior
     */
    public function build(string $propertyName, array $propertyPath): object;
}
