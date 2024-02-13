<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use const PHP_INT_MAX;

interface PropertyPathAccessInterface extends PropertyPathInterface
{
    /**
     * To use if the terminating relationship should be accessed as property on the entity (and
     * thus without join to the target entity of the relationship).
     *
     * This is especially relevant for cases like counting the targets in a to-many relationship. By using this constant
     * it would result in something like `COUNT(Book.authors)` returning the correct result. If {@link self::UNPACK}
     * was used instead a join to the `Person` entity/table was used instead and the result may be something like
     * `COUNT(person)`, and thus wrong.
     */
    public const DIRECT = 0;

    /**
     * To use if the join to the target entity should be executed. The join to the target entity
     * will be executed (e.g. a join from `Book.authors` to the `Person` entity).
     */
    public const UNPACK = 1;
    public const UNPACK_RECURSIVE = PHP_INT_MAX;

    /**
     * If the last segment in the path is a relationship this property determines if a join to the
     * target entity of that relationship should be executed.
     *
     * Common values:
     *
     * * {@link PropertyPathAccessInterface::DIRECT}
     * * {@link PropertyPathAccessInterface::UNPACK}
     *
     * Conditions that require a non-relationship as last property in the path should simply set
     * {@link PropertyPathAccessInterface::DIRECT}.
     *
     * @return int the value determining how often a join should be executed
     */
    public function getAccessDepth(): int;

    /**
     * Changes the path of this instance.
     *
     * @param non-empty-list<non-empty-string> $path
     *
     * @throws PathException
     */
    public function setPath(array $path): void;

    public function getSalt(): string;

    public function __toString(): string;

    /**
     * @return class-string|null
     */
    public function getContext(): ?string;
}
