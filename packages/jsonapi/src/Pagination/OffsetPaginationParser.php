<?php

declare(strict_types=1);

namespace EDT\JsonApi\Pagination;

use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\Querying\Pagination\OffsetPagination;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template-implements PaginationParserInterface<OffsetPagination>
 */
class OffsetPaginationParser implements PaginationParserInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @throws ValidationFailedException
     */
    public function getPagination(ParameterBag $urlParameters): ?object
    {
        if (!$urlParameters->has(UrlParameter::PAGE)) {
            return null;
        }

        $page = $urlParameters->get(UrlParameter::PAGE);
        $page = $this->getValidatedPage($page);

        /** @var int<0, max> $offset */
        $offset = (int) $page[UrlParameter::OFFSET];

        /** @var positive-int $limit */
        $limit = (int) $page[UrlParameter::LIMIT];

        return new OffsetPagination($offset, $limit);
    }

    /**
     * @param mixed $page
     *
     * @return array{offset: non-empty-string, limit: non-empty-string}
     *
     * @throws ValidationFailedException
     */
    protected function getValidatedPage($page): array
    {
        $violations = $this->validator->validate($page, [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\Collection([
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
            ], null, null, false, false),
        ]);
        if (0 !== $violations->count()) {
            throw new ValidationFailedException($page, $violations);
        }

        return $page;
    }
}
