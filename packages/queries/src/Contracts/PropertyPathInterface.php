<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use Traversable;

/**
 * @template-extends Traversable<int,string>
 */
interface PropertyPathInterface extends Traversable
{
    /**
     * Returns the names of the properties that are part of this path.
     *
     * @return array<int,string> Empty array if this path has no parent set. Otherwise, the complete path build so far.
     * @throws PathException Thrown if the array could not be generated.
     */
    public function getAsNames(): array;

    /**
     * Builds the path denoted by this instance using a dot notation. How the information of the path is
     * stored is left to the implementation. The path will consist of property names only, not including
     * specific information about the starting point.
     *
     * Example using three classes:
     * * <em>A</em>: has a relationship field <em>foo</em> to type <em>B</em>
     * * <em>B</em>: has a relationship field <em>bar</em> to type <em>C</em>
     * * <em>C</em>: has a non-relationship field <em>baz</em>
     *
     * Starting with <em>A</em> and denoting a path to the property <em>baz</em> would result in
     * <code>'foo.bar.baz'</code>.
     *
     * Starting with <em>A</em> and denoting a path to the relationship <em>bar</em> would result in
     * <code>'foo.bar'</code>.
     *
     * Starting with <em>A</em> and only denoting its relationship <em>foo</em> would result in
     * <code>'foo'</code>.
     *
     * Starting with <em>B</em> and denoting the property <em>baz</em> would result in
     * <code>'bar.baz'</code>.
     *
     * Starting with <em>C</em> and only denoting its property <em>baz</em> would result in
     * <code>'baz'</code>.
     *
     * @return string The path of the filter condition in dot notation. Empty string if this path has no parent set. Otherwise, the complete path build so far.
     *
     * @throws PathException Thrown if the string could not be generated.
     */
    public function getAsNamesInDotNotation(): string;
}
