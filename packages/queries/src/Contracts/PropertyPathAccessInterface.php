<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Traversable;
use const PHP_INT_MAX;

/**
 * @template-extends Traversable<int,string>
 */
interface PropertyPathAccessInterface extends PropertyPathInterface
{
    /**
     * To use if the terminating relationship should be accessed as property on the entity (and
     * thus without join to the target entity of the relationship).
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
     * @var int The value determining how often a join should be executed.
     */
    public function getAccessDepth(): int;

    /**
     * Changes the path of this instance.
     *
     * @throws PathException
     */
    public function setPath(string $property, string ...$properties): void;

    public function getSalt(): string;

    public function __toString(): string;

    /**
     * @return class-string|null
     */
    public function getContext(): ?string;
}
