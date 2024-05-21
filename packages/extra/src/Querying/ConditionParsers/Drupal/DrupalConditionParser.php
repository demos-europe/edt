<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Conditions\Equals;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Utilities\PathConverterTrait;
use function array_key_exists;

/**
 * Transform specific conditions inside a Drupal filter format that use the typical Drupal format.
 *
 * Conditions must contain a path and may contain a value and operator. If no operator is present, a default
 * operator will be used. If no value is given, it is expected that the operator can be applied without a value. If
 * this expectation is not met the behavior is undefined.
 *
 * @phpstan-import-type DrupalFilterCondition from DrupalFilterParser
 *
 * @template TCondition
 * @template-implements ConditionParserInterface<TCondition>
 */
class DrupalConditionParser implements ConditionParserInterface
{
    use PathConverterTrait;

    /**
     * @param DrupalConditionFactoryInterface<TCondition> $drupalConditionFactory
     * @param non-empty-string $defaultOperator
     */
    public function __construct(
        protected readonly DrupalConditionFactoryInterface $drupalConditionFactory,
        protected readonly string $defaultOperator = Equals::OPERATOR
    ) {}

    /**
     * @throws DrupalFilterException
     */
    public function parseCondition(array $condition)
    {
        $operatorName = array_key_exists(DrupalFilterParser::OPERATOR, $condition)
            ? $condition[DrupalFilterParser::OPERATOR]
            : $this->defaultOperator;

        if (array_key_exists(DrupalFilterParser::VALUE, $condition)
            && null === $condition[DrupalFilterParser::VALUE]) {
            throw DrupalFilterException::nullValue();
        }

        $pathString = $condition[DrupalFilterParser::PATH] ?? null;
        $path = null === $pathString
            ? null
            : self::inputPathToArray($pathString);

        return array_key_exists(DrupalFilterParser::VALUE, $condition)
            ? $this->drupalConditionFactory->createConditionWithValue($operatorName, $condition[DrupalFilterParser::VALUE], $path)
            : $this->drupalConditionFactory->createConditionWithoutValue($operatorName, $path);
    }
}
