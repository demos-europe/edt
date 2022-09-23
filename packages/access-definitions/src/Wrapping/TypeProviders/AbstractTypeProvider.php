<?php

declare(strict_types=1);

namespace EDT\Wrapping\TypeProviders;

use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Contracts\TypeRetrievalAccessException;
use EDT\Wrapping\Contracts\Types\TypeInterface;

/**
 * @template C of \EDT\Querying\Contracts\PathsBasedInterface
 * @template S of \EDT\Querying\Contracts\PathsBasedInterface
 *
 * @template-implements TypeProviderInterface<C, S>
 */
abstract class AbstractTypeProvider implements TypeProviderInterface
{
    public function getAvailableType(string $typeIdentifier, string ...$implementations): TypeInterface
    {
        $type = $this->getType($typeIdentifier, ...$implementations);
        if (!$type->isAvailable()) {
            throw TypeRetrievalAccessException::typeExistsButNotAvailable($typeIdentifier);
        }
        return $type;
    }

    public function getType(string $typeIdentifier, string ...$implementations): TypeInterface
    {
        $type = $this->getTypeInterface($typeIdentifier);

        foreach ($implementations as $implementation) {
            if (!is_a($type, $implementation)) {
                throw TypeRetrievalAccessException::noNameWithImplementation($typeIdentifier, $implementation);
            }
        }

        return $type;
    }

    public function getTypeInterface(string $typeIdentifier): TypeInterface
    {
        $type = $this->getTypeByIdentifier($typeIdentifier);
        if (null === $type) {
            throw TypeRetrievalAccessException::unknownTypeIdentifier($typeIdentifier, $this->getTypeIdentifiers());
        }

        return $type;
    }

    public function getTypeWithImplementation(string $typeIdentifier, string $implementation): TypeInterface
    {
        return $this->getType($typeIdentifier, $implementation);
    }

    public function getAvailableTypeWithImplementation(string $typeIdentifier, string $implementation): TypeInterface
    {
        $type = $this->getTypeWithImplementation($typeIdentifier, $implementation);
        if (!$type->isAvailable()) {
            throw TypeRetrievalAccessException::typeExistsButNotAvailable($typeIdentifier);
        }
        return $type;
    }

    /**
     * @throws TypeRetrievalAccessException
     */
    public function isTypeAvailable(string $typeIdentifier, string ...$implementations): bool
    {
        $type = $this->getTypeByIdentifier($typeIdentifier);
        if (null === $type) {
            return false;
        }

        foreach ($implementations as $implementation) {
            if (!is_a($type, $implementation)) {
                return false;
            }
        }

        return $type->isAvailable();
    }

    /**
     * @throws TypeRetrievalAccessException
     */
    abstract protected function getTypeByIdentifier(string $typeIdentifier): ?TypeInterface;

    /**
     * @return list<non-empty-string>
     */
    abstract protected function getTypeIdentifiers(): array;
}
