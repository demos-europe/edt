<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use Exception;
use function get_class;
use function gettype;
use function is_object;

class DrupalFilterException extends Exception
{
    public static function neitherConditionNorGroup(): self
    {
        return new self("Elements in filter MUST be either 'group' or 'condition'.");
    }

    public static function memberOfType(string $type): self
    {
        return new self("The 'memberOf' field value MUST be of type string, found: {$type}.");
    }

    public static function memberOfRoot(): self
    {
        return new self("The 'memberOf' field value MUST NOT be '@root', this value is internally reserved. To assign a condition or group to the implicit root group just omit the 'memberOf' field completely.");
    }

    public static function rootKeyUsed(): self
    {
        return new self("The name of a group or condition MUST NOT be '@root', this value is internally reserved.");
    }

    public static function unknownGroupField(string $fieldName): self
    {
        return new self("The 'group' field MUST NOT contain fields other than 'conjunction' and 'memberOf', found: {$fieldName}.");
    }

    public static function unknownConditionField(string $fieldName): self
    {
        return new self("The 'condition' field MUST NOT contain fields other than 'path', 'value', 'operator' and 'memberOf', found: {$fieldName}");
    }

    public static function noConjunction(): self
    {
        return new self("The 'group' field MUST contain the 'conjunction' field.");
    }

    public static function conjunctionType(string $type): self
    {
        return new self("The 'conjunction' value MUST be of type 'string', was {$type}.");
    }

    public static function noPath(): self
    {
        return new self("The 'path' field must always exist.");
    }

    public static function pathType(string $type): self
    {
        return new self("The 'path' field value must be a string, got {$type}.");
    }

    public static function nullValue(): self
    {
        return new self("The 'condition' must not contain a 'value' field with a null value.");
    }

    public static function unknownCondition(string $operatorName, string ...$availableOperatorNames): self
    {
        $operatorNames = implode(', ', $availableOperatorNames);
        return new self("No operator of such name is available: {$operatorName}. The following operators are available: $operatorNames");
    }

    public static function conjunctionUnavailable(string $name): self
    {
        return new self("The conjunction is not available: {$name}");
    }

    /**
     * @param mixed $group
     */
    public static function groupNonArray($group): self
    {
        $type = is_object($group) ? get_class($group) : gettype($group);
        return new self("Expected group to be an array, got '$type' instead.");
    }

    /**
     * @param mixed $condition
     */
    public static function conditionNonArray($condition): self
    {
        $type = is_object($condition) ? get_class($condition) : gettype($condition);
        return new self("Expected condition to be an array, got '$type' instead.");
    }

    public static function emergencyAbort(int $iterations): self
    {
        return new self("Can't build tree. Does it contain a loop (ie. a condition group referencing itself, directly or indirectly)? Aborted after $iterations iterations");
    }
}
