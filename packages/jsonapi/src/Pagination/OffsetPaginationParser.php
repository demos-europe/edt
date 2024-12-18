<?php

declare(strict_types=1);

namespace EDT\JsonApi\Pagination;

use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\Querying\Pagination\OffsetPagination;
use EDT\Querying\Utilities\CollectionConstraintFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template-implements PaginationParserInterface<OffsetPagination>
 */
class OffsetPaginationParser implements PaginationParserInterface
{
    private readonly CollectionConstraintFactory $collectionConstraintFactory;

    public function __construct(
        protected readonly ValidatorInterface $validator
    ) {
        $this->collectionConstraintFactory = new CollectionConstraintFactory();
    }

    /**
     * @throws ValidationFailedException
     */
    public function getPagination(ParameterBag $urlParameters): ?OffsetPagination
    {
        if (!$urlParameters->has(UrlParameter::PAGE)) {
            return null;
        }

        $page = $urlParameters->all(UrlParameter::PAGE);
        $page = $this->getValidatedPage($page);

        /** @var int<0, max> $offset */
        $offset = (int) $page[UrlParameter::OFFSET];

        /** @var positive-int $limit */
        $limit = (int) $page[UrlParameter::LIMIT];

        return new OffsetPagination($offset, $limit);
    }

    /**
     * @return array{offset: numeric-string, limit: numeric-string}
     *
     * @throws ValidationFailedException
     */
    protected function getValidatedPage(mixed $page): array
    {
        $violations = $this->validator->validate($page, [
            new Assert\NotNull(),
            new Assert\Type('array'),
            $this->collectionConstraintFactory->exactMatch('the page parameter', [
                UrlParameter::OFFSET => [
                    new Assert\NotNull(),
                    new Assert\Type('string'),
                    new Assert\PositiveOrZero(),
                ],
                UrlParameter::LIMIT => [
                    new Assert\NotNull(),
                    new Assert\Type('string'),
                    new Assert\Positive(),
                ],
            ]),
        ]);
        if (0 !== $violations->count()) {
            throw new ValidationFailedException($page, $violations);
        }

        \Webmozart\Assert\Assert::isArray($page);

        return $page;
    }
}
