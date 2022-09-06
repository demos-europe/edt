<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

/**
 * Meant to be used as input when instantiating the {@link DynamicTransformer}.
 *
 * It provides mandatory information for relationships.
 *
 * @template O of object
 * @template T of object
 * @template-extends PropertyDefinitionInterface<O, T|array<int, T>|null>
 */
interface IncludeDefinitionInterface extends PropertyDefinitionInterface
{
    /**
     * If this property is a to-many relationship. Otherwise, it is a to-one relationship.
     *
     * This determines if the data loaded from the entity property will be used to generate
     * a collection or an item.
     *
     * @param array<int|string, mixed>|object $propertyData the data stored in the entities property
     *                                                      this instance corresponds to
     */
    public function isToMany($propertyData): bool;

    /**
     * Relationships are transformed recursively, meaning each included property must define the
     * transformer to be used to transform itself.
     */
    public function getTransformer(): TransformerAbstract;

    /**
     * @return string The resource type of this include. Named "resource key" here to keep
     *                the naming consistent with the PHP Fractal library.
     */
    public function getResourceKey(): string;

    /**
     * > An endpoint MAY return resources related to the primary data by default.
     *
     * @return bool `true` if the property this instance corresponds to should be included by
     *              default in the response if no specific field set was requested, `false` otherwise
     *
     * @see https://jsonapi.org/format/#fetching-includes
     */
    public function isToBeUsedAsDefaultInclude(): bool;
}
