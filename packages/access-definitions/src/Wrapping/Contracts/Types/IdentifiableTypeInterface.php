<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts\Types;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Properties\IdReadabilityInterface;

/**
 * Defines the property {@link TypeInterface::getEntityClass() corresponding entities}
 * can be distinguished by.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 * @template TEntity of object
 *
 * @template-extends TypeInterface<TCondition, TSorting, TEntity>
 */
interface IdentifiableTypeInterface extends TypeInterface
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getIdentifierFilterPath(): array;

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function getIdentifierSortingPath(): array;

    /**
     * Provides a readability for the identifier that uniquely identifies an instance of the
     * {@link TypeInterface::getEntityClass() backing entity}.
     *
     * @return IdReadabilityInterface<TEntity>
     */
    public function getIdentifierReadability(): IdReadabilityInterface;
}
