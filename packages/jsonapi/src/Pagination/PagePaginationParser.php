<?php

declare(strict_types=1);

namespace EDT\JsonApi\Pagination;

use EDT\JsonApi\RequestHandling\UrlParameter;
use EDT\Querying\Pagination\PagePagination;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function array_key_exists;

/**
 * @template-implements PaginationParserInterface<PagePagination>
 */
class PagePaginationParser implements PaginationParserInterface
{
    /**
     * @var positive-int
     */
    private $defaultSize;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param positive-int $defaultSize
     */
    public function __construct(int $defaultSize, ValidatorInterface $validator)
    {
        $this->defaultSize = $defaultSize;
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

        /** @var positive-int $size */
        $size = array_key_exists(UrlParameter::SIZE, $page)
            ? (int) $page[UrlParameter::SIZE]
            : $this->defaultSize;

        /** @var positive-int $number */
        $number = array_key_exists(UrlParameter::NUMBER, $page)
            ? (int) $page[UrlParameter::NUMBER]
            : 1;

        return new PagePagination($size, $number);
    }

    /**
     * @param mixed $page
     *
     * @return array{size?: non-empty-string, number?: non-empty-string}
     *
     * @throws ValidationFailedException
     */
    protected function getValidatedPage($page): array
    {
        $violations = $this->validator->validate($page, [
            new Assert\NotNull(),
            new Assert\Type('array'),
            new Assert\Collection([
                UrlParameter::SIZE => [
                    new Assert\NotNull(),
                    new Assert\Type('string'),
                    new Assert\Positive(),
                ],
                UrlParameter::NUMBER => [
                    new Assert\NotNull(),
                    new Assert\Type('string'),
                    new Assert\Positive(),
                ],
            ], null, null, false, true),
        ]);
        if (0 !== $violations->count()) {
            throw new ValidationFailedException($page, $violations);
        }

        return $page;
    }

}
