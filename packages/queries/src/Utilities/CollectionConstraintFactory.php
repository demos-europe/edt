<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;

class CollectionConstraintFactory
{
    /**
     * Creates a {@link Collection} constraint that only matches an array that contains exactly the given field names,
     * no more and no less.
     *
     * @param non-empty-string $context
     * @param non-empty-array<non-empty-string, list<Constraint>> $fields the exact set of fields that must be present
     */
    public function exactMatch(string $context, array $fields): Collection
    {
        return new Collection(
            ['fields' => $fields],
            null,
            null,
            false,
            false,
            $this->createExtraMessage($context, $fields),
            $this->createMissingMessage($context, $fields)
        );
    }

    /**
     * Creates a {@link Collection} constraint that only matches an array that no other field names than the given ones.
     * Not all allowed fields need to be present in the array though, it may even be empty.
     *
     * @param non-empty-string $context a string to adjust the violation message
     * @param array<non-empty-string, list<Constraint>> $allowedFields
     */
    public function noExtra(string $context, array $allowedFields): Collection
    {
        return new Collection(
            ['fields' => $allowedFields],
            null,
            null,
            false,
            true,
            $this->createExtraMessage($context, $allowedFields)
        );
    }

    /**
     * Creates a {@link Collection} constraint that only matches an array that contains all the given field names.
     * Additional fields are allowed to be present in the array though.
     *
     * @param non-empty-string $context a string to adjust the violation message
     * @param non-empty-array<non-empty-string, list<Constraint>> $requiredFields
     */
    public function noMissing(string $context, array $requiredFields): Collection
    {
        return new Collection(
            ['fields' => $requiredFields],
            null,
            null,
            true,
            false,
            null,
            $this->createMissingMessage($context, $requiredFields)
        );
    }

    /**
     * @param non-empty-string $context a string to adjust the violation message
     * @param array<non-empty-string, mixed> $allowedFields
     */
    protected function createExtraMessage(string $context, array $allowedFields): string
    {
        return [] === $allowedFields
            ? "Unexpected field(s) in the context of $context, none are allowed."
            : "Unexpected field(s) in the context of $context, allowed fields are: {$this->implodeNames($allowedFields)}";
    }

    /**
     * @param non-empty-string $context a string to adjust the violation message
     * @param non-empty-array<non-empty-string, mixed> $requiredFields
     */
    protected function createMissingMessage(string $context, array $requiredFields): string
    {
        return "Missing field(s) in the context of $context, required are: {$this->implodeNames($requiredFields)}";
    }

    /**
     * @param non-empty-array<non-empty-string, mixed> $fields
     *
     * @return non-empty-string
     */
    protected function implodeNames(array $fields): string
    {
        $fieldNames = array_keys($fields);

        $fieldNames = array_map(
            static fn (string $name): string => "`$name`",
            $fieldNames
        );

        return implode(', ', $fieldNames);
    }
}
