<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\Querying\Contracts\ConditionFactoryInterface;use EDT\Querying\Contracts\FunctionInterface;
use ReflectionClass;
use ReflectionException;
use function array_filter;
use function array_map;
use function get_declared_classes;
use function in_array;
use function Safe\class_implements;

class GuessingConditionParser extends DrupalConditionParser
{
    /**
     * @var string
     */
    private $classSuffix;

    public function __construct(ConditionFactoryInterface $conditionFactory, string $defaultOperator = 'Equals', string $classSuffix = 'DrupalOperator')
    {
        parent::__construct($conditionFactory, $defaultOperator);
        $this->classSuffix = $classSuffix;
    }

    /**
     * @param mixed $conditionValue
     * @return FunctionInterface<bool>
     * @throws DrupalFilterException
     * @throws ReflectionException
     */
    protected function createCondition(string $conditionName, $conditionValue, string $pathPart, string ...$pathParts): FunctionInterface
    {
        array_unshift($pathParts, $pathPart);
        $className = "$conditionName$this->classSuffix";
        if (class_exists($className)) {
            $operatorInstance = new $className($this->conditionFactory, $conditionValue, $pathParts);
            if ($operatorInstance instanceof FunctionInterface) {
                return $operatorInstance;
            }
        }

        $availableClasses = array_filter(get_declared_classes(), function (string $className): bool {
            $implementations = class_implements($className);
            return in_array(FunctionInterface::class, $implementations, true)
                && str_ends_with($className, $this->classSuffix);
        });
        $availableOperators = array_map(static function (string $className): string {
            return (new ReflectionClass($className))->getShortName();
        }, $availableClasses);
        throw DrupalFilterException::unknownCondition($conditionName, ...$availableOperators);
    }
}
