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
    public const DIRECT = 0;
    public const UNPACK = 1;
    public const UNPACK_RECURSIVE = PHP_INT_MAX;

    /**
     * If the last property in the path is a relationship this property determines if a join to the
     * target entity of that relationship should be executed.
     *
     * Conditions that require a non-relationship as last property in the path should simply set
     * {@link PropertyPathAccessInterface::DIRECT}.
     *
     * 0 if the terminating relationship should be accessed as property on the entity
     * (and thus without join to the target entity of the relationship).
     *
     * 1 if the join to the target entity should be
     * executed. The join to the target entity will be executed (eg. a join from
     * `Book.authors` to the `Person` entity).
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
}
