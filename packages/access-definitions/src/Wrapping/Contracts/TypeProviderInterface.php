<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * Returns {@link TypeInterface} instances for given Type identifiers.
 *
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
interface TypeProviderInterface
{
    /**
     * @return TypeInterface<TCondition, TSorting, object>|null
     *
     * @throws TypeRetrievalAccessException
     */
    public function getTypeByIdentifier(string $typeIdentifier): ?TypeInterface;

    /**
     * @return list<non-empty-string>
     */
    public function getTypeIdentifiers(): array;
}
