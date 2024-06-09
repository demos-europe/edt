<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\DrupalFilterInterface;
use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\ConditionFactory\ConditionGroupFactoryInterface;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TCondition
 */
class ConditionConverter
{
    /**
     * @param DrupalFilterParser<TCondition> $filterTransformer
     */
    public function __construct(
        protected readonly DrupalFilterParser $filterTransformer,
        protected readonly DrupalFilterValidator $filterValidator,
    ) {}

    /**
     * @template TCond
     *
     * @param ConditionFactoryInterface<TCond>&ConditionGroupFactoryInterface<TCond> $conditionFactory
     *
     * @return ConditionConverter<TCond>
     *
     * @deprecated use {@link DefaultConverterFactory::createConditionConverter} instead
     */
    public static function createDefault(ValidatorInterface $validator, ConditionFactoryInterface&ConditionGroupFactoryInterface $conditionFactory): self
    {
        $converterFactory = new DefaultConverterFactory($validator);

        return $converterFactory->createConditionConverter($conditionFactory);
    }

    /**
     * @param list<DrupalFilterInterface> $conditions
     *
     * @return list<TCondition>
     */
    public function convertConditions(array $conditions): array
    {
        return array_merge(...array_map(
            function (DrupalFilterInterface $filter): array {
                $filterArray = $filter->toDrupalArray('root');
                $this->filterValidator->validateFilter($filterArray);

                return $this->filterTransformer->parseFilter($filterArray);
            },
            $conditions
        ));
    }
}
