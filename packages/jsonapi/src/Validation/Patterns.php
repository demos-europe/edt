<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

/**
 * @internal
 */
class Patterns
{
    /**
     * A property name must consist of letters, digits or underscores, but not start with a digit,
     * underscore or upper-case letter.
     *
     * This is the intersection of valid PHP property names and
     * {@link https://jsonapi.org/format/1.1/#document-member-names valid JSON:API member names}.
     *
     * For example matches:
     *
     * * `foo`
     * * `fooBar`
     *
     * but not:
     *
     * * `foo.bar`
     * * `foo,bar`
     */
    public const PROPERTY_NAME = '[a-z]\w*';

    /**
     * A comma separated list of one or more property names.
     *
     * For example matches:
     *
     * * `foo`
     * * `foo,fooBar`
     * * `foo,foo,bar`
     *
     * but not:
     *
     * * `foo.bar`
     */
    public const PROPERTY_NAME_LIST = self::PROPERTY_NAME.'(,'.self::PROPERTY_NAME.')*';

    /**
     * A dot separated list of one or more property names.
     *
     * For example matches:
     *
     * * `foo`
     * * `foo.fooBar`
     * * `foo.foo.bar`
     *
     * but not:
     *
     * * `foo,bar`
     */
    public const PROPERTY_PATH = self::PROPERTY_NAME.'(\.'.self::PROPERTY_NAME.')*';

    public const SORT_PROPERTY = '-?'.Patterns::PROPERTY_PATH;
}
