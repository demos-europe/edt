<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\Contracts\PropertyPathInterface;

/**
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
class MutableDrupalCondition extends AbstractDrupalFilter
{
    /**
     * @param non-empty-string $type
     * @param array<non-empty-string, mixed> $content
     */
    public function __construct(
        protected readonly string $type,
        protected readonly array $content
    ) {}

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     * @param non-empty-string $operator
     * @param DrupalValue $value
     */
    public static function createWithValue(
        string|array|PropertyPathInterface $path,
        string $operator,
        mixed $value
    ): self {
        $content = [
            DrupalFilterParser::PATH => self::pathToString($path),
            DrupalFilterParser::VALUE => $value,
            DrupalFilterParser::OPERATOR => $operator,
        ];

        return new self(DrupalFilterParser::CONDITION, $content);
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     * @param non-empty-string $operator
     */
    public static function createWithoutValue(string|array|PropertyPathInterface $path, string $operator): self
    {
        $content = [
            DrupalFilterParser::PATH => self::pathToString($path),
            DrupalFilterParser::OPERATOR => $operator,
        ];

        return new self(DrupalFilterParser::CONDITION, $content);
    }

    public function toDrupalArray(string $name, string $memberOf = ''): array
    {
        $content = $this->content;

        if ('' !== $memberOf) {
            $content[DrupalFilterParser::MEMBER_OF] = $memberOf;
        }

        return [
            $name => [
                $this->type => $content,
            ],
        ];
    }

    public function deepCopy(callable $contentRewriter = null): AbstractDrupalFilter
    {
        $content = null === $contentRewriter
            ? $this->content
            : $contentRewriter($this->content);

        return new self($this->type, $content);
    }
}
