<?php

declare(strict_types=1);

namespace EDT\ConditionFactory;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;

/**
 * @phpstan-import-type DrupalFilterCondition from DrupalFilterParser
 * @phpstan-import-type DrupalFilterGroup from DrupalFilterParser
 * @phpstan-import-type DrupalValue from DrupalFilterParser
 */
class MutableDrupalGroup extends AbstractDrupalFilter
{
    /**
     * @var list<array{namePrefix: string, filter: AbstractDrupalFilter}>
     */
    protected array $children = [];

    /**
     * @param 'AND'|'OR' $conjunction
     */
    public function __construct(
        protected string $conjunction
    ) {}

    public static function createAnd(): self
    {
        return new self(DrupalFilterParser::AND);
    }

    public static function createOr(): self
    {
        return new self(DrupalFilterParser::OR);
    }

    public function addChild(AbstractDrupalFilter $filter, string $namePrefix): void
    {
        $this->children[] = [
            'namePrefix' => $namePrefix,
            'filter' => $filter,
        ];
    }

    public function toDrupalArray(string $name, string $memberOf = ''): array
    {
        $identifier = spl_object_id($this);
        $thisGroupName = $this->createName($name, null);

        $convertedChildren = [];
        foreach ($this->children as $index => ['namePrefix' => $childPrefix, 'filter' => $filter]) {
            $childName = $this->createName("{$childPrefix}_$identifier", $index);
            $convertedChildren[] = $filter->toDrupalArray($childName, $thisGroupName);
        }

        $result = array_merge(...$convertedChildren);
        $result[$thisGroupName] = $this->createDrupalGroup($this->conjunction, $memberOf);

        return $result;
    }

    public function deepCopy(callable $contentRewriter = null): AbstractDrupalFilter
    {
        $copy = new self($this->conjunction);
        foreach ($this->children as ['namePrefix' => $namePrefix, 'filter' => $filter]) {
            $copy->addChild($filter->deepCopy(), $namePrefix);
        }

        return $copy;
    }

    /**
     * @param 'AND'|'OR' $conjunction
     *
     * @return array{group: DrupalFilterGroup}
     */
    protected function createDrupalGroup(string $conjunction, string $memberOf): array
    {
        $content = [
            DrupalFilterParser::CONJUNCTION => $conjunction,
        ];

        if ('' !== $memberOf) {
            $content[DrupalFilterParser::MEMBER_OF] = $memberOf;
        }

        return [
            DrupalFilterParser::GROUP => $content,
        ];
    }

    /**
     * @param non-empty-string $identifier
     * @return non-empty-string
     */
    protected function createName(string $identifier, ?int $index): string
    {
        return null === $index ? $identifier : "$identifier-$index";

    }
}
