<?php

declare(strict_types=1);

namespace EDT\Querying\Drupal;

interface StandardOperator
{
    public const EQUALS = '=';
    public const IS_NULL = 'IS NULL';
    public const STARTS_WITH_CASE_INSENSITIVE = 'STARTS_WITH_CASE_INSENSITIVE';
    public const NOT_BETWEEN = 'NOT BETWEEN';
    public const IS_NOT_NULL = 'IS NOT NULL';
    public const IN = 'IN';
    public const NOT_EQUALS = '<>';
    public const ARRAY_CONTAINS_VALUE = 'ARRAY_CONTAINS_VALUE';
    public const LT = '<';
    public const STRING_CONTAINS_CASE_INSENSITIVE = 'STRING_CONTAINS_CASE_INSENSITIVE';
    public const BETWEEN = 'BETWEEN';
    public const NOT_IN = 'NOT_IN';
    public const GT = '>';
    public const ENDS_WITH_CASE_INSENSITIVE = 'ENDS_WITH_CASE_INSENSITIVE';
    public const LTEQ = '<=';
    public const GTEQ = '>=';
}
