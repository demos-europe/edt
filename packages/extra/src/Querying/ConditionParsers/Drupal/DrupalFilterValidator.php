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
     * @var list<Constraint>
     */
    private $filterNameConstraints;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator, OperatorProviderInterface $operatorProvider)
    {
        $this->filterSchemaConstraints = $this->getFilterSchemaConstraints($operatorProvider->getAllOperatorNames());
        $this->filterNameConstraints = $this->getFilterNameConstraints();
        $this->validator = $validator;
    }

    /**
     * @param mixed $filter
     *
     * @throws DrupalFilterException
     */
    public function validateFilter($filter): void
    {
        $filterSchemaViolations = $this->validator->validate($filter, $this->filterSchemaConstraints);
        $filterNameViolations = $this->validator->validate(array_keys($filter), $this->filterNameConstraints);
        $filterSchemaViolations->addAll($filterNameViolations);

        if (0 !== $filterSchemaViolations->count()) {
            throw new DrupalFilterException(
                'Schema validation of filter data failed.',
                0,
                new ValidationFailedException($filter, $filterSchemaViolations)
            );
        }
    }

    /**
     * @param list<non-empty-string> $validOperatorNames
     *
     * @return list<Constraint>
     */
    protected function getFilterSchemaConstraints(array $validOperatorNames): array
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

    /**
     * @return list<Constraint>
     */
    protected function getFilterNameConstraints(): array
    {
        return [
            new Assert\All([
                // Must not be empty.
                new Assert\NotBlank(),
                // Must consist of letters, digits, underscores or hyphens.
                new Assert\Regex('/\A[-\w]+\z/'),
                // Must not be a number, as these are problematic due to PHP's
                // automatic array key type conversion.
                new Assert\Regex('/\A-?\d+\z/', null, null, false),
                new Assert\Type('string'),
                // Must not be the reserved root group.
                new Assert\Regex('/\A'.DrupalFilterParser::ROOT.'\z/', null, null, false),
            ]),
        ];
    }
}
