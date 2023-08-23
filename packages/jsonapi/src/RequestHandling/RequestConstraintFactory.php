<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\Wrapping\Contracts\ContentField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class RequestConstraintFactory
{
    use RequestConstraintTrait;

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return list<Constraint>
     */
    public function getBodyConstraints(
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollection $expectedProperties
    ): array {
        return [
            new Assert\Collection(
                [
                    ContentField::DATA => [
                        // validate attributes and relationships
                        new Assert\Collection(
                            [
                                ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier),
                                ContentField::ID => $this->getIdConstraints($urlId),
                                ContentField::ATTRIBUTES    => [
                                    // validate required attributes are present
                                    new Assert\Collection(
                                        $expectedProperties->getRequiredAttributes(),
                                        null,
                                        null,
                                        true,
                                        false
                                    ),
                                    // validate request attributes are allowed and valid
                                    new Assert\Collection(
                                        $expectedProperties->getAllowedAttributes(),
                                        null,
                                        null,
                                        false,
                                        true
                                    ),
                                ],
                                ContentField::RELATIONSHIPS => [
                                    // validate required relationships are present
                                    new Assert\Collection(
                                        $expectedProperties->getRequiredRelationships(),
                                        null,
                                        null,
                                        true,
                                        false
                                    ),
                                    // validate request relationships are allowed and valid
                                    new Assert\Collection(
                                        $expectedProperties->getAllowedRelationships(),
                                        null,
                                        null,
                                        false,
                                        true
                                    ),
                                ],
                            ],
                            null,
                            null,
                            false,
                            true
                        ),
                        // validate `type` field
                        new Assert\Collection(
                            [
                                ContentField::TYPE => $this->getTypeIdentifierConstraints($urlTypeIdentifier),
                            ],
                            null,
                            null,
                            true,
                            false
                        ),
                        // validate `id` field (only required if an ID was given in the request)
                        new Assert\Collection(
                            [
                                ContentField::ID => $this->getIdConstraints($urlId),
                            ],
                            null,
                            null,
                            true,
                            null === $urlId
                        ),
                    ],
                ],
                null,
                null,
                false,
                false
            ),
        ];
    }
}
