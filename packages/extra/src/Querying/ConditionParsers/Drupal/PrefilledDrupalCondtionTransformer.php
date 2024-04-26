<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Conditions\ConditionInterface;
use EDT\Querying\Conditions\ValueDependentConditionInterface;
use EDT\Querying\Conditions\ValueIndependentConditionInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 *
 * @template-implements DrupalConditionFactoryInterface<TCondition>
 */
class PrefilledDrupalCondtionTransformer implements DrupalConditionFactoryInterface
{
    /**
     * @var array<non-empty-string, ValueDependentConditionInterface<TCondition>|ValueIndependentConditionInterface<TCondition>>
     */
    protected readonly array $operators;

    /**
     * @param list<ValueDependentConditionInterface<TCondition>|ValueIndependentConditionInterface<TCondition>> $operators
     */
    public function __construct(array $operators)
    {
        $operatorDictionary = [];
        foreach ($operators as $operator) {
            $operatorName = $operator->getOperator();
            Assert::keyNotExists($operatorDictionary, $operatorName);
            $operatorDictionary[$operatorName] = $operator;
        }
        $this->operators = $operatorDictionary;
    }

    public function getSupportedOperators(): array
    {
        return array_map(
            static fn (ConditionInterface $operator) => $operator->getFormatConstraints(),
            $this->operators
        );
    }

    public function createConditionWithValue(string $operatorName, float|int|bool|array|string|null $value, ?array $path): PathsBasedInterface
    {
        $operator = $this->getOperator($operatorName);
        Assert::isInstanceOf($operator, ValueDependentConditionInterface::class);

        return $operator->transform($path, $value);
    }

    public function createConditionWithoutValue(string $operatorName, ?array $path): PathsBasedInterface
    {
        $operator = $this->getOperator($operatorName);
        Assert::isInstanceOf($operator, ValueIndependentConditionInterface::class);

        return $operator->transform($path);
    }

    /**
     * @param non-empty-string $operatorName
     *
     * @return ValueDependentConditionInterface<TCondition>|ValueIndependentConditionInterface<TCondition>
     *
     * @throws InvalidArgumentException the operator was not found
     */
    protected function getOperator(string $operatorName): ValueDependentConditionInterface|ValueIndependentConditionInterface
    {
        Assert::keyExists($this->operators, $operatorName);

        return $this->operators[$operatorName];
    }
}
