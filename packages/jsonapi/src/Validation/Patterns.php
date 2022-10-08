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
     * This is the intersection of valid PHP property names and valid JSON:API member names.
     */
    public const PROPERTY_NAME = '[a-z]\w*';

    /**
     * A comma separated list of one or more property names.
     */
    public const PROPERTY_NAME_LIST = self::PROPERTY_NAME.'(,'.self::PROPERTY_NAME.')*';

    /**
     * A dot separated list of one or more property names.
     */
    public const PROPERTY_PATH = self::PROPERTY_NAME.'(.'.self::PROPERTY_NAME.')*';
}
