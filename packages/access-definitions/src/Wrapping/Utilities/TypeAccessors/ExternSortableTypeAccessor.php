<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template-extends AbstractTypeAccessor<SortableTypeInterface<PathsBasedInterface, PathsBasedInterface, object>>
 */
class ExternSortableTypeAccessor extends AbstractTypeAccessor
{
    public function getProperties(TypeInterface $type): array
    {
        return $type->getSortableProperties();
    }

    public function getType(string $typeIdentifier): TypeInterface
    {
        return $this->typeProvider->requestType($typeIdentifier)
            ->instanceOf(SortableTypeInterface::class)
            ->available(true)
            ->getTypeInstance();
    }
}