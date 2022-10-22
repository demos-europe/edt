<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use function array_key_exists;

/**
 * Parses specific conditions inside a Drupal filter format.
 *
 * @psalm-type DrupalFilterCondition = array{
 *            path: non-empty-string,
 *            value?: mixed,
 *            operator?: non-empty-string,
 *            memberOf?: non-empty-string
 *          }
 * @template TCondition of \EDT\Querying\Contracts\PathsBasedInterface
 * @template-implements ConditionParserInterface<DrupalFilterCondition, TCondition>
 */
class DrupalConditionParser implements ConditionParserInterface
{
    /**
     * @var non-empty-string
     */
    protected string $defaultOperator;

    /**
     * @var DrupalConditionFactoryInterface<TCondition>
     */
    private DrupalConditionFactoryInterface $drupalConditionFactory;

    /**
     * @param DrupalConditionFactoryInterface<TCondition> $drupalConditionFactory
     * @param non-empty-string                            $defaultOperator
     */
    public function __construct(
        DrupalConditionFactoryInterface $drupalConditionFactory,
        string $defaultOperator = '='
    ) {
        $this->defaultOperator = $defaultOperator;
        $this->drupalConditionFactory = $drupalConditionFactory;
    }

    /**
     * @param array{path: non-empty-string, value?: mixed, operator?: non-empty-string, memberOf?: non-empty-string} $condition
     *
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
