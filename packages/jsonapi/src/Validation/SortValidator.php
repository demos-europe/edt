<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SortValidator
{
    /**
     * @var non-empty-list<Constraint>
     */
    private array $sortConstraints;

    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
        $this->sortConstraints = [
            new Assert\NotBlank(null, null, false),
            new Assert\Type('string'),
            new Assert\Regex('/^'.Patterns::SORT_PROPERTY.'(,'.Patterns::SORT_PROPERTY.')*$/'),
        ];
    }

    /**
     * @return non-empty-string
     */
    public function validateFormat(mixed $sortValue): string
    {
        $violations = $this->validator->validate($sortValue, $this->sortConstraints);

        if (0 !== $violations->count()) {
            throw new SortException(
                'Invalid format used for \'sort\' parameter.',
                0,
                new ValidationFailedException($sortValue, $violations)
            );
        }

        return $sortValue;
    }
}
