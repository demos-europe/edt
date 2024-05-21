<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;

use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Querying\Utilities\PathConverterTrait;
use Webmozart\Assert\Assert;

/**
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
class MutableDrupalCondition extends AbstractDrupalFilter
{
    use PathConverterTrait;

    /**
     * @param non-empty-string $type
     * @param array<non-empty-string, mixed> $content
     */
    public function __construct(
        protected readonly string $type,
        protected array $content
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

    public function deepCopy(callable $contentRewriter = null): DrupalFilterInterface
    {
        $content = null === $contentRewriter
            ? $this->content
            : $contentRewriter($this->content);

        return new self($this->type, $content);
    }

    public function adjustPath(callable $callable): void
    {
        $pathString = $this->content[DrupalFilterParser::PATH] ?? null;
        if (null !== $pathString) {
            Assert::stringNotEmpty($pathString);
            $oldPath = self::pathToArray($pathString);
            Assert::allStringNotEmpty($oldPath);
            $newPath = $callable($oldPath);
            $this->content[DrupalFilterParser::PATH] = static::pathToString($newPath);
        }
    }
}
