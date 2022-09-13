<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use League\Fractal\TransformerAbstract;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template T of object
 * @template-extends ReadableTypeInterface<C, T>
 * @template-extends IdentifiableTypeInterface<C, T>
 * @template-extends FilterableTypeInterface<C, T>
 * @template-extends SortableTypeInterface<C, T>
 */
interface ResourceTypeInterface extends ReadableTypeInterface, FilterableTypeInterface, SortableTypeInterface, IdentifiableTypeInterface
{
    /**
     * @return non-empty-string
     */
    public static function getName(): string;

    /**
     * Provides the transformer to convert instances of
     * {@link ResourceTypeInterface::getEntityClass} into JSON.
     */
    public function getTransformer(): TransformerAbstract;
}
