<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\ConditionFactory\ConditionFactoryInterface;
use EDT\ConditionFactory\ConditionGroupFactoryInterface;
use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\ConditionParsers\Drupal\DrupalConditionParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Creates new instance by initializing some necessary utility class instances automatically.
 *
 * Using this class has the advantage to skip the choice of implementation details (i.e. the utility class
 * implementations to use) and simply instantiate the desired instance.
 *
 * The disadvantage is that new, potentially redundant instances of those utility classes are initialized. If you
 * already use a dependency injection framework like Symfony, you may want to utilize it instead of using this class.
 */
class DefaultConverterFactory
{
    protected ?SortValidator $sortingValidator = null;

    public function __construct(
        protected readonly ValidatorInterface $validator
    ) {}

    /**
     * @template TCond
     *
     * @param ConditionFactoryInterface<TCond>&ConditionGroupFactoryInterface<TCond> $conditionFactory
     *
     * @return ConditionConverter<TCond>
     */
    public function createConditionConverter(
        ConditionFactoryInterface&ConditionGroupFactoryInterface $conditionFactory
    ): ConditionConverter {
        $drupalConditionFactory = new PredefinedDrupalConditionFactory($conditionFactory);
        $drupalConditionParser = new DrupalConditionParser($drupalConditionFactory);
        $drupalFilterValidator = new DrupalFilterValidator($this->validator, $drupalConditionFactory);
        $filterTransformer = new DrupalFilterParser($conditionFactory, $drupalConditionParser, $drupalFilterValidator);

        return new ConditionConverter($filterTransformer, $drupalFilterValidator);
    }

    /**
     * @template TSort
     *
     * @param SortMethodFactoryInterface<TSort> $sortMethodFactory
     *
     * @return SortMethodConverter<TSort>
     */
    public function createSortMethodConverter(SortMethodFactoryInterface $sortMethodFactory): SortMethodConverter {
        $sortingTransformer = new JsonApiSortingParser($sortMethodFactory);

        if (null === $this->sortingValidator) {
            $this->sortingValidator = new SortValidator($this->validator);
        }

        return new SortMethodConverter($sortingTransformer, $this->sortingValidator);
    }
}