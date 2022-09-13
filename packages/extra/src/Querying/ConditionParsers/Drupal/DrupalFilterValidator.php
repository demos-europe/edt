<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DrupalFilterValidator
{
    /**
     * @var list<Constraint>
     */
    private $filterSchemaConstraints;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator, OperatorProviderInterface $operatorProvider)
    {
        $this->filterSchemaConstraints = $this->getFilterConstraints($operatorProvider->getAllOperatorNames());
        $this->validator = $validator;
    }

    /**
     * @param mixed $filter
     *
     * @throws DrupalFilterException
     */
    public function validateFilter($filter): void
    {
        $filterViolations = $this->validator->validate($filter, $this->filterSchemaConstraints);
        if (0 !== $filterViolations->count()) {
            throw new DrupalFilterException(
                'Schema validation of filter data failed.',
                0,
                new ValidationFailedException($filter, $filterViolations)
            );
        }
    }

    /**
     * @param list<non-empty-string> $validOperatorNames
     *
     * @return list<Constraint>
     */
    private function getFilterConstraints(array $validOperatorNames): array
    {
        $stringConstraint = new Assert\Type('string');
        $arrayConstraint = new Assert\Type('array');
        $conjunctionConstraint = new Assert\Choice([DrupalFilterParser::AND, DrupalFilterParser::OR]);
        $conditionConstraints = [
            $arrayConstraint,
            new Assert\Collection(
                [
                    DrupalFilterParser::VALUE     => null,
                    DrupalFilterParser::MEMBER_OF => $stringConstraint,
                    DrupalFilterParser::PATH      => $stringConstraint, // TODO: add non-empty path + non-empty path segment validation
                    DrupalFilterParser::OPERATOR  => new Assert\Choice($validOperatorNames),
                ],
                null,
                null,
                false,
                true
            ),
            new Assert\Collection(
                [
                    DrupalFilterParser::PATH => $stringConstraint,
                ],
                null,
                null,
                true,
                false
            ),
        ];
        $groupConstraints = [
            $arrayConstraint,
            new Assert\Collection(
                [
                    DrupalFilterParser::MEMBER_OF => $stringConstraint,
                    DrupalFilterParser::CONJUNCTION => $conjunctionConstraint,
                ],
                null,
                null,
                false,
                true
            ),
            new Assert\Collection(
                [
                    DrupalFilterParser::CONJUNCTION => $conjunctionConstraint,
                ],
                null,
                null,
                true,
                false
            ),
        ];

        /** @var mixed $assertionsOnRootItems (avoid incorrect type concern) */
        $assertionsOnRootItems = [
            $arrayConstraint,
            new Assert\Count(1),
            new Assert\Collection(
                [
                    DrupalFilterParser::CONDITION => $conditionConstraints,
                    DrupalFilterParser::GROUP     => $groupConstraints,
                ],
                null,
                null,
                false,
                true
            ),
        ];

        return [
            $arrayConstraint,
            new Assert\All($assertionsOnRootItems),
        ];
    }
}
