<?php

declare(strict_types=1);

namespace EDT\Querying\ConditionParsers\Drupal;

use EDT\JsonApi\Validation\Patterns;
use EDT\Querying\Utilities\CollectionConstraintFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DrupalFilterValidator
{
    /**
     * @var list<Constraint>
     */
    protected array $filterSchemaConstraints;

    /**
     * @var list<Constraint>
     */
    protected array $filterNamesConstraints;

    private CollectionConstraintFactory $collectionConstraintFactory;

    public function __construct(
        protected ValidatorInterface $validator,
        DrupalConditionFactoryInterface $drupalConditionFactory
    ) {
        $this->collectionConstraintFactory = new CollectionConstraintFactory();
        $this->filterNamesConstraints = $this->getFilterNamesConstraints();
        $this->filterSchemaConstraints = $this->getFilterSchemaConstraints($drupalConditionFactory->getSupportedOperators());
    }

    /**
     * @param mixed $filter the filter needs to correspond to a specific format, but just for the
     *                      validation any kind of value is allowed here
     *
     * @throws DrupalFilterException
     */
    public function validateFilter(mixed $filter): void
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
        /** @var mixed $assertionsOnRootItems (avoid incorrect type concern) */
        $assertionsOnRootItems = [
            new Assert\Type('array'),
            new Assert\Count(1),
            $this->collectionConstraintFactory->noExtra('filter items', $this->getFilterTypeConstraints($validOperatorNames)),
        ];

        return [
            new Assert\Type('array'),
            new Assert\All($assertionsOnRootItems),
        ];
    }

    /**
     * @param list<non-empty-string> $validOperatorNames
     *
     * @return array{condition: list<Constraint>, group: list<Constraint>}
     */
    protected function getFilterTypeConstraints(array $validOperatorNames): array
    {
        return [
            DrupalFilterParser::CONDITION => $this->getConditionConstraints($validOperatorNames),
            DrupalFilterParser::GROUP     => $this->getGroupConstraints(),
        ];
    }

    /**
     * @param list<non-empty-string> $validOperatorNames
     *
     * @return list<Constraint>
     */
    protected function getConditionConstraints(array $validOperatorNames): array
    {
        $filterNameConstraints = $this->getFilterNameConstraints();
        $pathConstraints = [
            new Assert\Type('string'),
            new Assert\NotBlank(null, null, false, 'trim'),
            new Assert\Regex('/^'.Patterns::PROPERTY_PATH.'$/'),
        ];

        return [
            new Assert\Type('array'),
            $this->collectionConstraintFactory->noExtra('a filter condition', [
                DrupalFilterParser::VALUE => [
                    new Assert\AtLeastOneOf([
                        new Assert\IsNull(),
                        new Assert\Type('string'),
                        new Assert\Type('array'),
                        new Assert\Type('float'),
                        new Assert\Type('bool'),
                        new Assert\Type('int'),
                    ])
                ],
                DrupalFilterParser::MEMBER_OF => $filterNameConstraints,
                DrupalFilterParser::PATH => $pathConstraints,
                DrupalFilterParser::OPERATOR => [new Assert\Choice($validOperatorNames)],
            ]),
            $this->collectionConstraintFactory->noMissing('a filter condition', [
                DrupalFilterParser::PATH => $pathConstraints,
            ]),
        ];
    }

    /**
     * @return list<Constraint>
     */
    protected function getGroupConstraints(): array
    {
        $conjunctionConstraint = $this->getGroupConjunctionConstraints();
        $filterNameConstraints = $this->getFilterNameConstraints();

        $fields = [
            DrupalFilterParser::MEMBER_OF => $filterNameConstraints,
            DrupalFilterParser::CONJUNCTION => $conjunctionConstraint,
        ];

        return [
            new Assert\Type('array'),
            $this->collectionConstraintFactory->noExtra('a filter group', $fields),
            $this->collectionConstraintFactory->noMissing('a filter group', [
                DrupalFilterParser::CONJUNCTION => $conjunctionConstraint,
            ]),
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

    /**
     * @return non-empty-list<Constraint>
     */
    protected function getGroupConjunctionConstraints(): array
    {
        return [
            new Assert\NotBlank(null, null, false),
            new Assert\Type('string'),
            new Assert\Choice(
                [DrupalFilterParser::AND, DrupalFilterParser::OR],
                null,
                null,
                false,
                true,
                1,
                1
            ),
        ];
    }
}
