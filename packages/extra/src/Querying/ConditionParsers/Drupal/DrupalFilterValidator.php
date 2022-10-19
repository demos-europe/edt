<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\JsonApi\Validation\Patterns;
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
    private $filterNamesConstraints;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator, DrupalConditionFactoryInterface $drupalConditionFactory)
    {
        $this->filterNamesConstraints = $this->getFilterNamesConstraints();
        $this->filterSchemaConstraints = $this->getFilterSchemaConstraints($drupalConditionFactory->getSupportedOperators());
        $this->validator = $validator;
    }

    /**
     * @param mixed $filter the filter needs to correspond to a specific format, but just for the
     *                      validation any kind of value is allowed here
     *
     * @throws DrupalFilterException
     */
    public function validateFilter($filter): void
    {
        $filterSchemaViolations = $this->validator->validate($filter, $this->filterSchemaConstraints);
        $filterNameViolations = $this->validator->validate(array_keys($filter), $this->filterNamesConstraints);
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
        $arrayConstraint = new Assert\Type('array');
        $conjunctionConstraint = new Assert\Choice([DrupalFilterParser::AND, DrupalFilterParser::OR]);
        $pathConstraints = [
            new Assert\Type('string'),
            new Assert\NotBlank(null, null, false, 'trim'),
            new Assert\Regex('/^'.Patterns::PROPERTY_PATH.'$/'),
        ];
        $filterNameConstraints = $this->getFilterNameConstraints();
        $conditionConstraints = [
            $arrayConstraint,
            new Assert\Collection(
                [
                    DrupalFilterParser::VALUE     => null,
                    DrupalFilterParser::MEMBER_OF => $filterNameConstraints,
                    DrupalFilterParser::PATH      => $pathConstraints,
                    DrupalFilterParser::OPERATOR  => new Assert\Choice($validOperatorNames),
                ],
                null,
                null,
                false,
                true
            ),
            new Assert\Collection(
                [
                    DrupalFilterParser::PATH => $pathConstraints,
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
                    DrupalFilterParser::MEMBER_OF => $filterNameConstraints,
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
    protected function getFilterNamesConstraints(): array
    {
        return [
            new Assert\All($this->getFilterNameConstraints()),
        ];
    }

    /**
     * @return list<Constraint>
     */
    protected function getFilterNameConstraints(): array
    {
        return [
            // Must not be empty.
            new Assert\NotBlank(null, null, false),
            // Must consist of letters, digits, underscores or hyphens.
            new Assert\Regex('/\A[-\w]+\z/'),
            // Must not be a number, as these are problematic due to PHP's
            // automatic array key type conversion.
            new Assert\Regex('/\A-?\d+\z/', null, null, false),
            new Assert\Type('string'),
            // Must not be the reserved root group.
            new Assert\Regex('/\A'.DrupalFilterParser::ROOT.'\z/', null, null, false),
        ];
    }
}
