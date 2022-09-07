<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;
use function gettype;
use function is_string;

/**
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
     * The key of the field determining which filter group a condition or a subgroup is a member
     * of.
     *
     * @var string
     */
    protected const MEMBER_OF = 'memberOf';
    /**
     * @var string
     */
    protected const VALUE = 'value';
    /**
     * @var string
     */
    protected const PATH = 'path';
    /**
     * @var string
     */
    protected const OPERATOR = 'operator';
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
                case self::PATH:
                case self::VALUE:
                case self::MEMBER_OF:
                case self::OPERATOR:
                    break;
                default:
                    throw DrupalFilterException::unknownConditionField($key);
            }
        }

        $operatorString = array_key_exists(self::OPERATOR, $condition)
            ? $condition[self::OPERATOR]
            : $this->defaultOperator;

        if (array_key_exists(self::VALUE, $condition) && null === $condition[self::VALUE]) {
            throw DrupalFilterException::nullValue();
        }

        return $this->createCondition(
            $operatorString,
            $condition[self::VALUE] ?? null,
            ...explode('.', $condition[self::PATH])
        );
    }

    /**
     * @param mixed|null $conditionValue
     * @return F
     * @throws DrupalFilterException
     */
    abstract protected function createCondition(string $conditionName, $conditionValue, string $pathPart, string ...$pathParts): FunctionInterface;
}
