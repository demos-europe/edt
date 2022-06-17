<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;
use EDT\Querying\Contracts\ConditionParserInterface;
use EDT\Querying\Contracts\FunctionInterface;
use function array_key_exists;
use function gettype;
use function is_string;

abstract class DrupalConditionParser implements ConditionParserInterface
{
    /**
     * The key of the field determining which filter group a condition or a sub group is a member
     * of.
     *
     * @var string
     */
    protected const MEMBER_OF_KEY = 'memberOf';
    /**
     * @var string
     */
    protected const VALUE_KEY = 'value';
    /**
     * @var string
     */
    protected const PATH_KEY = 'path';
    /**
     * @var string
     */
    protected const OPERATOR_KEY = 'operator';
    /**
     * @var string
     */
    protected $defaultOperator;
    /**
     * @var ConditionFactoryInterface
     */
    protected $conditionFactory;

    public function __construct(ConditionFactoryInterface $conditionFactory, string $defaultOperator = '=')
    {
        $this->conditionFactory = $conditionFactory;
        $this->defaultOperator = $defaultOperator;
    }

    /**
     * @param array<string,mixed> $condition
     * @return FunctionInterface<bool>
     * @throws DrupalFilterException
     */
    public function parseCondition(array $condition): FunctionInterface
    {
        foreach ($condition as $key => $value) {
            switch ($key) {
                case self::PATH_KEY:
                case self::VALUE_KEY:
                case self::MEMBER_OF_KEY:
                case self::OPERATOR_KEY:
                    break;
                default:
                    throw DrupalFilterException::unknownConditionField($key);
            }
        }
        if (!array_key_exists(self::PATH_KEY, $condition)) {
            throw DrupalFilterException::noPath();
        }
        $path = $condition[self::PATH_KEY];
        if (!is_string($path)) {
            throw DrupalFilterException::pathType(gettype($path));
        }

        $operatorString = array_key_exists(self::OPERATOR_KEY, $condition)
            ? $condition[self::OPERATOR_KEY]
            : $this->defaultOperator;

        if (array_key_exists(self::VALUE_KEY, $condition) && null === $condition[self::VALUE_KEY]) {
            throw DrupalFilterException::nullValue();
        }

        return $this->createCondition(
            $operatorString,
            $condition[self::VALUE_KEY] ?? null,
            ...explode('.', $path)
        );
    }

    /**
     * @param mixed $conditionValue
     * @return FunctionInterface<bool>
     * @throws DrupalFilterException
     */
    abstract protected function createCondition(string $conditionName, $conditionValue, string $pathPart, string ...$pathParts): FunctionInterface;
}
