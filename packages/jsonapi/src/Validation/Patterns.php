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
}
