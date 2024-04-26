<?php

declare(strict_types=1);

namespace EDT\Querying\Drupal;

use EDT\Querying\Conditions\ArrayContains;
use EDT\Querying\Conditions\Between;
use EDT\Querying\Conditions\ConditionInterface;
use EDT\Querying\Conditions\EndsWith;
use EDT\Querying\Conditions\Equals;
use EDT\Querying\Conditions\GreaterThan;
use EDT\Querying\Conditions\GreaterEqualsThan;
use EDT\Querying\Conditions\In;
use EDT\Querying\Conditions\IsNotNull;
use EDT\Querying\Conditions\IsNull;
use EDT\Querying\Conditions\LesserThan;
use EDT\Querying\Conditions\LesserEqualsThan;
use EDT\Querying\Conditions\NotBetween;
use EDT\Querying\Conditions\NotEquals;
use EDT\Querying\Conditions\NotIn;
use EDT\Querying\Conditions\StartsWith;
use EDT\Querying\Conditions\StringContains;

/**
 * @deprecated Use constants defined by children of {@link ConditionInterface} instead
 */
interface StandardOperator
{
    public const EQUALS = Equals::OPERATOR;
    public const IS_NULL = IsNull::OPERATOR;
    public const STARTS_WITH_CASE_INSENSITIVE = StartsWith::OPERATOR;
    public const NOT_BETWEEN = NotBetween::OPERATOR;
    public const IS_NOT_NULL = IsNotNull::OPERATOR;
    public const IN = In::OPERATOR;
    public const NOT_EQUALS = NotEquals::OPERATOR;
    public const ARRAY_CONTAINS_VALUE = ArrayContains::OPERATOR;
    public const LT = LesserThan::OPERATOR;
    public const STRING_CONTAINS_CASE_INSENSITIVE = StringContains::OPERATOR;
    public const BETWEEN = Between::OPERATOR;
    public const NOT_IN = NotIn::OPERATOR;
    public const GT = GreaterThan::OPERATOR;
    public const ENDS_WITH_CASE_INSENSITIVE = EndsWith::OPERATOR;
    public const LTEQ = LesserEqualsThan::OPERATOR;
    public const GTEQ = GreaterEqualsThan::OPERATOR;
}
