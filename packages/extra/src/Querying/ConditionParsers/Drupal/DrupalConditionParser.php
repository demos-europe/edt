<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function array_key_exists;

/**
 * Parses specific conditions inside a Drupal filter format.
 *
 * @phpstan-import-type DrupalFilterCondition from DrupalFilterParser
 *
 * @template TCondition of PathsBasedInterface
 * @template-implements ConditionParserInterface<DrupalFilterCondition, TCondition>
 */
class DrupalConditionParser implements ConditionParserInterface
{
    /**
     * @param DrupalConditionFactoryInterface<TCondition> $drupalConditionFactory
     * @param non-empty-string $defaultOperator
     */
    public function __construct(
        private readonly DrupalConditionFactoryInterface $drupalConditionFactory,
        protected readonly string $defaultOperator = '='
    ) {}

    /**
     * @throws DrupalFilterException
     */
    public function parseCondition($condition): PathsBasedInterface
    {
        $operatorName = array_key_exists(DrupalFilterParser::OPERATOR, $condition)
            ? $condition[DrupalFilterParser::OPERATOR]
            : $this->defaultOperator;

        if (array_key_exists(DrupalFilterParser::VALUE, $condition)
            && null === $condition[DrupalFilterParser::VALUE]) {
            throw DrupalFilterException::nullValue();
        }

        $value = $condition[DrupalFilterParser::VALUE] ?? null;

        $pathString = $condition[DrupalFilterParser::PATH];
        $path = array_map(static function (string $pathSegment) use ($operatorName, $pathString): string {
            if ('' === $pathSegment) {
                throw DrupalFilterException::emptyPathSegment($operatorName, $pathString);
            }

            return $pathSegment;
        }, explode('.', $pathString));

        return $this->drupalConditionFactory->createCondition($operatorName, $value, $path);
    }
}
