<?php

declare(strict_types=1);

namespace EDT\JsonApi\InputHandling;

use EDT\JsonApi\RequestHandling\JsonApiSortingParser;
use EDT\JsonApi\Validation\SortValidator;
use EDT\Querying\Contracts\SortMethodFactoryInterface;
use EDT\Querying\SortMethodFactories\SortMethodInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TSorting the type of sort methods this instance shall convert to
 */
class SortMethodConverter
{
    /**
     * @param JsonApiSortingParser<TSorting> $sortingTransformer
     */
    public function __construct(
        protected readonly JsonApiSortingParser $sortingTransformer,
        protected readonly SortValidator $sortingValidator,
    ) {}

    /**
     * @template TSort
     *
     * @param SortMethodFactoryInterface<TSort> $sortMethodFactory
     *
     * @return SortMethodConverter<TSort>
     */
    public static function createDefault(ValidatorInterface $validator, SortMethodFactoryInterface $sortMethodFactory)
    {
        $sortingTransformer = new JsonApiSortingParser($sortMethodFactory);
        $sortingValidator = new SortValidator($validator);

        return new self($sortingTransformer, $sortingValidator);
    }

    /**
     * @param list<SortMethodInterface> $sortMethods
     *
     * @return list<TSorting>
     */
    public function convertSortMethods(array $sortMethods): array
    {
        if ([] !== $sortMethods) {
            $sortMethodsString = implode(',', array_map(
                static fn(SortMethodInterface $sortMethod): string => $sortMethod->getAsString(),
                $sortMethods
            ));
            $sortMethodsString = $this->sortingValidator->validateFormat($sortMethodsString);
            $sortMethods = $this->sortingTransformer->createFromQueryParamValue($sortMethodsString);
        }

        return $sortMethods;
    }
}
