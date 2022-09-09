<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;

/**
 * Parses specific conditions inside a Drupal filter format.
 *
 * @psalm-type DrupalFilterCondition = array{
 *            path: string,
 *            value?: mixed,
 *            operator?: string,
 *            memberOf?: string
 *          }
 * @template F of FunctionInterface<bool>
 * @template-implements ConditionParserInterface<DrupalFilterCondition, F>
 */
abstract class DrupalConditionParser implements ConditionParserInterface
{
    /**
     * @var string
     */
    protected $defaultOperator;
    /**
     * @var ConditionFactoryInterface<F>
     */
    protected $conditionFactory;

    /**
     * @param ConditionFactoryInterface<F> $conditionFactory
     */
    public function __construct(ConditionFactoryInterface $conditionFactory, string $defaultOperator = '=')
    {
        $this->conditionFactory = $conditionFactory;
        $this->defaultOperator = $defaultOperator;
    }

    /**
     * @throws DrupalFilterException
     */
    public function parseCondition($condition): FunctionInterface
    {
        foreach ($condition as $key => $value) {
            switch ($key) {
                case DrupalFilterParser::PATH:
                case DrupalFilterParser::VALUE:
                case DrupalFilterParser::MEMBER_OF:
                case DrupalFilterParser::OPERATOR:
                    break;
                default:
                    throw DrupalFilterException::unknownConditionField($key);
            }
        }

        $operatorString = array_key_exists(DrupalFilterParser::OPERATOR, $condition)
            ? $condition[DrupalFilterParser::OPERATOR]
            : $this->defaultOperator;

        if (array_key_exists(
                DrupalFilterParser::VALUE, $condition) && null === $condition[DrupalFilterParser::VALUE]) {
            throw DrupalFilterException::nullValue();
        }

        return $this->createCondition(
            $operatorString,
            $condition[DrupalFilterParser::VALUE] ?? null,
            ...explode('.', $condition[DrupalFilterParser::PATH])
        );
    }

    /**
     * @param mixed|null $conditionValue
     * @return F
     * @throws DrupalFilterException
     */
    abstract protected function createCondition(string $conditionName, $conditionValue, string $pathPart, string ...$pathParts): FunctionInterface;
}
