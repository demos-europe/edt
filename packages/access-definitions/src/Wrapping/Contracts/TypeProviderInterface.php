<?php

declare(strict_types=1);

namespace EDT\Wrapping\Contracts;

use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * Returns {@link TypeInterface} instances for given Type identifiers.
 *
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 */
interface TypeProviderInterface
{
    /**
     * @template I of TypeInterface
     *
     * @param non-empty-string $typeIdentifier The identifier of your type, used when referencing other types.
     * @param class-string<I>  ...$implementations
     *
     * @return TypeInterface<C, S, object>&I
     *
     * @throws TypeRetrievalAccessException
     */
    public function getType(string $typeIdentifier, string ...$implementations): TypeInterface;

    /**
     * Alternative to {@link TypeProviderInterface::getType()} that may be better understood
     * by code analysing tools.
     *
     * @param non-empty-string $typeIdentifier
     *
     * @return TypeInterface<C, S, object>
     */
    public function getTypeInterface(string $typeIdentifier): TypeInterface;

    /**
     * Alternative to {@link TypeProviderInterface::getType()} that may be better understood
     * by code analysing tools.
     *
     * @template I of TypeInterface
     *
     * @param non-empty-string $typeIdentifier
     * @param class-string<I>  $implementation
     *
     * @return TypeInterface<C, S, object>&I
     */
    public function getTypeWithImplementation(string $typeIdentifier, string $implementation): TypeInterface;

    /**
     * @template I of TypeInterface
     *
     * @param non-empty-string $typeIdentifier The identifier of your type, used when referencing other types.
     * @param class-string<I> ...$implementations The fully qualified namespaces that the type must implement.
     *
     * @return TypeInterface<C, S, object>&I
     *
     * @throws TypeRetrievalAccessException Thrown if a type with the given identifier and implementations is not available.
     */
    public function getAvailableType(string $typeIdentifier, string ...$implementations): TypeInterface;

    /**
     * Alternative to {@link TypeProviderInterface::getAvailableType()} that may be better understood
     * by code analysing tools.
     *
     * @template I of TypeInterface
     *
     * @param non-empty-string $typeIdentifier
     * @param class-string<I>  $implementation
     *
     * @return TypeInterface<C, S, object>&I
     */
    public function getAvailableTypeWithImplementation(string $typeIdentifier, string $implementation): TypeInterface;
}
