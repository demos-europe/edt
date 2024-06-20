<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities\Types;

use OutOfBoundsException;

/**
 * Instances represent a class or interface.
 * 
 * Supports template parameters on the represented class/interface.
 */
interface TypeInterface
{
    /**
     * If this parameter is a class, the result will include all its template parameters.
     *
     * If `$withSimpleClassNames` is set to `true`, both the type name and the (class/interface)
     * template parameters will be shortened to the single (non-fully qualified) name.
     * You can use {@link self::getAllFullyQualifiedNames()} to get the omitted fully qualified names.
     *
     * @param bool $withSimpleClassNames if set to `true` potentially existing class names will be returned with their simple name; if set to `false` the class names will be fully qualified
     *
     * @return non-empty-string
     */
    public function getFullString(bool $withSimpleClassNames): string;

    /**
     * Returns all class and interface names involved in this template parameter, including nested ones.
     *
     * E.g. `array<string, string>` will return nothing, while `Collection<int, Foo>` will return two items.
     *
     * @return list<class-string>
     */
    public function getAllFullyQualifiedNames(): array;

    /**
     * Returns `null` if the backing type of this instance does not allow for a single FQCN.
     *
     * @return class-string|null the fully qualified class name or `null` if such does not exist for this instance
     */
    public function getFullyQualifiedName(): ?string;

    /**
     * Expects that the given index denotes a template parameter.
     *
     * @param int $index starts with 0, negative values will denote the template parameters in reverse (i.e. from the end to the front); overflows (in either direction) are not allowed
     *
     * @throws OutOfBoundsException if the given index does not denote any existing template parameter
     */
    public function getTemplateParameter(int $index): TypeInterface;
}
