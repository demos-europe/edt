<?php

declare(strict_types=1);

namespace EDT\JsonApi\ResourceTypes;

use EDT\Wrapping\Contracts\Types\FilterableTypeInterface;
use EDT\Wrapping\Contracts\Types\IdentifiableTypeInterface;
use EDT\Wrapping\Contracts\Types\ReadableTypeInterface;
use EDT\Wrapping\Contracts\Types\SortableTypeInterface;
use League\Fractal\TransformerAbstract;

/**
 * @template T of object
 * @template-extends ReadableTypeInterface<T>
 * @template-extends IdentifiableTypeInterface<T>
 * @template-extends FilterableTypeInterface<T>
 * @template-extends SortableTypeInterface<T>
 */
interface ResourceTypeInterface extends ReadableTypeInterface, FilterableTypeInterface, SortableTypeInterface, IdentifiableTypeInterface
{
    /**
     * Always CamelCase.
     */
    public static function getName(): string;

    /**
     * Provides the transformer to convert instances of
     * {@link ResourceTypeInterface::getEntityClass} into JSON.
     */
    public function getTransformer(): TransformerAbstract;
}
