<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\Requests\RequestException;
use EDT\JsonApi\Validation\Patterns;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use JsonException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function array_key_exists;
use const JSON_THROW_ON_ERROR;

class RequestTransformer
{
    use RequestConstraintTrait;

    /**
     * @param TypeProviderInterface<PathsBasedInterface, PathsBasedInterface> $typeProvider
     */
    public function __construct(
        protected readonly RequestStack $requestStack,
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly ValidatorInterface $validator
    ) {}

    public function getUrlParameters(): ParameterBag
    {
        return $this->getRequest()->query;
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     *
     * @return CreationRequestBody
     * @throws RequestException
     */
    public function getCreationRequestBody(
        string $urlTypeIdentifier,
        ExpectedPropertyCollection $expectedProperties
    ): CreationRequestBody {
        $body = $this->getRequestData(
            $urlTypeIdentifier,
            null,
            $expectedProperties
        );
        $relationships = $body[ContentField::RELATIONSHIPS] ?? [];
        [$toOneRelationships, $toManyRelationships] = $this->splitRelationships($relationships);

        return new CreationRequestBody(
            $body[ContentField::ID] ?? null,
            $body[ContentField::TYPE],
            $body[ContentField::ATTRIBUTES] ?? [],
            $toOneRelationships,
            $toManyRelationships
        );
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string $urlId
     */
    public function getUpdateRequestBody(
        string $urlTypeIdentifier,
        string $urlId,
        ExpectedPropertyCollection $expectedProperties
    ): UpdateRequestBody {
        $body = $this->getRequestData(
            $urlTypeIdentifier,
            $urlId,
            $expectedProperties
        );
        $relationships = $body[ContentField::RELATIONSHIPS] ?? [];
        [$toOneRelationships, $toManyRelationships] = $this->splitRelationships($relationships);

        return new UpdateRequestBody(
            $urlId,
            $body[ContentField::TYPE],
            $body[ContentField::ATTRIBUTES] ?? [],
            $toOneRelationships,
            $toManyRelationships
        );
    }

    /**
     * @throws RequestException
     */
    protected function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw RequestException::noRequest();
        }

        return $request;
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return array{id?: non-empty-string, type: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}
     *
     * @throws ValidationFailedException
     * @throws RequestException
     */
    protected function getRequestData(
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollection $expectedProperties
    ): array {
        $requestBody = $this->getRequestBody($this->getRequest());
        $this->validateRequestBodyFormat(
            $requestBody,
            $urlTypeIdentifier,
            $urlId,
            $expectedProperties
        );
        $this->validateRequestBodyPropertyNames($requestBody);

        return $requestBody[ContentField::DATA];
    }

    /**
     * @param JsonApiRelationships $relationships
     *
     * @return array{0: array<non-empty-string, JsonApiRelationship|null>, 1: array<non-empty-string, list<JsonApiRelationship>>}
     */
    protected function splitRelationships(array $relationships): array
    {
        $toOneRelationships = [];
        $toManyRelationships = [];
        foreach ($relationships as $propertyName => $relationship) {
            $data = $relationship[ContentField::DATA];
            if (null === $data) {
                $toOneRelationships[$propertyName] = null;
            } elseif (array_key_exists(ContentField::ID, $data)) {
                $id = $data[ContentField::ID];
                \Webmozart\Assert\Assert::stringNotEmpty($id);
                $type = $data[ContentField::TYPE];
                \Webmozart\Assert\Assert::stringNotEmpty($type);
                $toOneRelationships[$propertyName] = [
                    ContentField::ID => $id,
                    ContentField::TYPE => $type,
                ];
            } else {
                foreach ($data as $index => $relationshipReference) {
                    \Webmozart\Assert\Assert::integer($index);
                    \Webmozart\Assert\Assert::isArray($relationshipReference);
                    $id = $data[ContentField::ID];
                    \Webmozart\Assert\Assert::stringNotEmpty($id);
                    $type = $data[ContentField::TYPE];
                    \Webmozart\Assert\Assert::stringNotEmpty($type);
                    $toManyRelationships[$propertyName][$index] = [
                        ContentField::ID   => $id,
                        ContentField::TYPE => $type,
                    ];
                }
            }
        }

        return [$toOneRelationships, $toManyRelationships];
    }

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @phpstan-assert array{data: array{id?: non-empty-string, type: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}} $body
     *
     * @throws ValidationFailedException
     */
    protected function validateRequestBodyFormat(
        mixed $body,
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollection $expectedProperties
    ): void {
        $constraints = $this->getBodyConstraints($urlTypeIdentifier, $urlId, $expectedProperties);
        $violations = $this->validator->validate($body, $constraints);

        if (0 !== $violations->count()) {
            throw new ValidationFailedException($body, $violations);
        }
    }

    /**
     * @param array{data: array{id?: non-empty-string, type: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}} $body
     *
     * @throws ValidationFailedException
     */
    protected function validateRequestBodyPropertyNames(array $body): void
    {
        $propertyNameConstraints = [
            new Assert\NotNull(),
            new Assert\Type('string'),
            // attribute and relationship names must adhere to a specific string format
            new Assert\Regex('/^'.Patterns::PROPERTY_NAME.'$/'),
            // attributes and relationships must not be named `id`
            new Assert\NotIdenticalTo(ContentField::ID),
            // attributes and relationships must not be named `type`
            new Assert\NotIdenticalTo(ContentField::TYPE),
            // attributes and relationships must use distinct names
            new Assert\Unique(),
        ];

        $propertyNames = array_merge(
            array_keys($body[ContentField::DATA][ContentField::ATTRIBUTES] ?? []),
            array_keys($body[ContentField::DATA][ContentField::RELATIONSHIPS] ?? [])
        );

        $violations = $this->validator->validate($propertyNames, $propertyNameConstraints);
        if (0 !== $violations->count()) {
            throw new ValidationFailedException($body, $violations);
        }
    }

    /**
     * @param int<1, 512> $maxDepth TODO: if non-primitive types are allowed as attributes, the default $maxDepth may need to be increased
     *
     * @throws RequestException
     */
    protected function getRequestBody(Request $request, int $maxDepth = 8): mixed
    {
        $content = $request->getContent();
        \Webmozart\Assert\Assert::string($content);
        try {
            return json_decode($content, true, $maxDepth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw RequestException::requestBody($content, $exception);
        }
    }


    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return list<Constraint>
     */
    protected function getBodyConstraints(
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
