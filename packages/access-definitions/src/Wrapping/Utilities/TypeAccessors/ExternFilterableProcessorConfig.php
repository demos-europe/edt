<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\FilteringTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template-extends AbstractProcessorConfig<FilteringTypeInterface<PathsBasedInterface, PathsBasedInterface>>
 */
class ExternFilterableProcessorConfig extends AbstractProcessorConfig
{
    /**
     * @param FilteringTypeInterface<PathsBasedInterface, PathsBasedInterface>&TypeInterface<PathsBasedInterface, PathsBasedInterface, object> $rootType
     */
    public function __construct(
        FilteringTypeInterface&TypeInterface $rootType
    ) {
        parent::__construct($rootType);
    }

    public function getProperties(object $type): array
    {
        return $type->getFilteringProperties();
    }
}
