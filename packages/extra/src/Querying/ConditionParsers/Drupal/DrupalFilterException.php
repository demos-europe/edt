<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\JsonApi\RequestHandling\FilterException;

class DrupalFilterException extends FilterException
{
    public static function neitherConditionNorGroup(string $name): self
    {
        return new self("Invalid filter element '$name'. MUST contain either 'group' or 'condition' as key.");
    }

    public static function memberOfRoot(): self
    {
        return new self("The 'memberOf' field value MUST NOT be '@root', this value is internally reserved. To assign a condition or group to the implicit root group just omit the 'memberOf' field completely.");
    }

    public static function rootKeyUsed(): self
    {
        return new self("The name of a group or condition MUST NOT be '@root', this value is internally reserved.");
    }

    public static function nullValue(): self
    {
        return new self("The 'condition' must not contain a 'value' field with a null value.");
    }

    public static function unknownCondition(string $operatorName, string ...$availableOperatorNames): self
    {
        $operatorNames = implode(', ', $availableOperatorNames);
        return new self("No operator of such name is available: $operatorName. The following operators are available: $operatorNames");
    }

    public static function conjunctionUnavailable(string $name): self
    {
        return new self("The conjunction is not available: $name");
    }

    public static function emergencyAbort(int $iterations): self
    {
        return new self("Can't build tree. Does it contain a loop (ie. a condition group referencing itself, directly or indirectly)? Aborted after $iterations iterations");
    }
}
