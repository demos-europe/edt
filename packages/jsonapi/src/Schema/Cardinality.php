<?php

declare(strict_types=1);

namespace EDT\JsonApi\Schema;

/**
 * Represents the cardinality of a relationship. Currently only toOne and toMany are implemented.
 *
 * Note that the creation of multiple equal instances is possible. E.g. multiple instances of 'toOne'
 * cardinalities may be created. Ideally for each cardinality at most one instance exists however
 * this is not implemented yet.
 *
 * Not allowing access to the internally stored strings 'toOne' and 'toMany' is intentional
 * to force the usage of types throughout the code and thus enabling static code analysis.
 */
class Cardinality
{
    /**
     * @param 'toOne'|'toMany' $cardinalityType
     */
    private function __construct(
        private readonly string $cardinalityType
    ) {}

    public static function getToMany(): Cardinality
    {
        return new self('toMany');
    }

    public static function getToOne(): Cardinality
    {
        return new self('toOne');
    }

    public function isToOne(): bool
    {
        return 'toOne' === $this->cardinalityType;
    }

    public function isToMany(): bool
    {
        return 'toMany' === $this->cardinalityType;
    }
}
