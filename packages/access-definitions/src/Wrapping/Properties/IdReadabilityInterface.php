<?php

declare(strict_types=1);

namespace EDT\Wrapping\Properties;

use Exception;

/**
 * Provides readability information and behavior for an attribute (i.e. a non-relationship) property.
 *
 * @template TEntity of object
 */
interface IdReadabilityInterface extends PropertySchemaInterface
{
    /**
     * Read the value of the attribute represented by this instance from the given entity.
     *
     * @param TEntity $entity
     *
     * @return non-empty-string
     *
     * @throws Exception
     */
    public function getValue(object $entity): string;
}
