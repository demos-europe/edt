<?php

declare(strict_types=1);

namespace EDT\Wrapping\Utilities\TypeAccessors;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\PropertyPaths\PropertyLink;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * Implementing this class allows to limit the access to properties by different
 * criteria, e.g. the {@link ExternFilterableProcessorConfig} will only allow access
 * to filterable properties and types.
 *
 * @template TType of TypeInterface<PathsBasedInterface, PathsBasedInterface, object>
 */
abstract class AbstractProcessorConfig
{
    /**
     * @param TypeProviderInterface<PathsBasedInterface, PathsBasedInterface> $typeProvider
     * @param TType $rootType
     */
    public function __construct(
        protected readonly TypeProviderInterface $typeProvider,
        private readonly TypeInterface $rootType
    ) {}

    /**
     * @return TType
     */
    public function getRootType(): TypeInterface
    {
        return $this->rootType;
    }

    /**
     * Get actually available properties of the given {@link TypeInterface type}.
     *
     * @param TType $type
     *
     * @return array<non-empty-string, PropertyLink<TType>>
     */
    abstract public function getProperties(TypeInterface $type): array;
}
