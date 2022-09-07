<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use League\Fractal\ParamBag;

/**
 * Meant to be used as input when instantiating the {@link DynamicTransformer}.
 *
 * It provides mandatory information for attributes and relationships. Relationships require
 * additional information (see {@link IncludeDefinitionInterface}).
 *
 * @template O of object
 * @template R
 */
interface PropertyDefinitionInterface
{
    /**
     * Returns data from the given object. The property accessed is the
     * one this instance was configured to access.
     *
     * @param O        $entity
     * @param ParamBag $params Contains optional content that can be used to adjust the data to
     *                         be returned. See {@link https://fractal.thephpleague.com/transformers/#include-parameters fractal include-parameters} for more information.
     *
     * @return R the data stored in the entity in the property this instance corresponds to
     */
    public function determineData(object $entity, ParamBag $params);

    /**
     * > If a client does not specify the set of fields for a given resource type, the server MAY
     * > send all fields, a subset of fields, or no fields for that resource type.
     *
     * @return bool `true` if the property this instance corresponds to should be present by
     *              default in the response if no specific field set was requested, `false`
     *              otherwise
     *
     * @see https://jsonapi.org/format/#fetching-sparse-fieldsets
     */
    public function isToBeUsedAsDefaultField(): bool;
}
