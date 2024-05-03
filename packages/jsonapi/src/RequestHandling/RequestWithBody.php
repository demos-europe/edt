<?php

declare(strict_types=1);

namespace EDT\JsonApi\RequestHandling;

use EDT\JsonApi\RequestHandling\Body\CreationRequestBody;
use EDT\JsonApi\RequestHandling\Body\UpdateRequestBody;
use EDT\JsonApi\Requests\RequestException;
use EDT\JsonApi\Validation\Patterns;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use JsonException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function array_key_exists;
use const JSON_THROW_ON_ERROR;

abstract class RequestWithBody
{
    use RequestConstraintTrait;

    /**
     * @param int<1, 8192> $maxBodyNestingDepth see {@link self::getRequestBody}
     */
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly RequestConstraintFactory $requestConstraintFactory,
        protected readonly Request $request,
        protected readonly int $maxBodyNestingDepth
    ) {}

    /**
     * @param non-empty-string $urlTypeIdentifier
     * @param non-empty-string|null $urlId
     *
     * @return array{id?: non-empty-string, type: non-empty-string, attributes?: array<non-empty-string, mixed>, relationships?: JsonApiRelationships}
     *
     * @throws ValidationFailedException
     * @throws RequestException
     */
    public function getRequestData(
        string $urlTypeIdentifier,
        ?string $urlId,
        ExpectedPropertyCollectionInterface $expectedProperties
    ): array {
        $requestBody = $this->getRequestBody($this->maxBodyNestingDepth);
        $this->requestConstraintFactory->validate(
            $this->validator,
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
     * @return array{array<non-empty-string, JsonApiRelationship|null>, array<non-empty-string, list<JsonApiRelationship>>}
     */
    public function splitRelationships(array $relationships): array
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
                $toManyRelationships[$propertyName] = [];
                foreach ($data as $index => $relationshipReference) {
                    \Webmozart\Assert\Assert::integer($index);
                    \Webmozart\Assert\Assert::isArray($relationshipReference);
                    $id = $relationshipReference[ContentField::ID];
                    \Webmozart\Assert\Assert::stringNotEmpty($id);
                    $type = $relationshipReference[ContentField::TYPE];
                    \Webmozart\Assert\Assert::stringNotEmpty($type);
                    $toManyRelationships[$propertyName][] = [
                        ContentField::ID   => $id,
                        ContentField::TYPE => $type,
                    ];
                }
            }
        }

        return [$toOneRelationships, $toManyRelationships];
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
            new Assert\Type('array'),
            // attributes and relationships must use distinct names
            new Assert\Unique(),
            // the following constraints must apply to each property name
            new Assert\All([
                new Assert\NotNull(),
                new Assert\Type('string'),
                // attribute and relationship names must adhere to a specific string format
                new Assert\Regex('/^'.Patterns::PROPERTY_NAME.'$/'),
                // attributes and relationships must not be named `id`
                new Assert\NotIdenticalTo(ContentField::ID),
                // attributes and relationships must not be named `type`
                new Assert\NotIdenticalTo(ContentField::TYPE),
            ])
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
     * @param int<1, 8192> $maxDepth The maximum allowed depth when parsing the JSON body. Structures with a nesting
     *                               exceeding the type-hinted maximum seem quite unlikely, but we will
     *                               refrain from throwing an exception just in case there is a use case requiring
     *                               deeper nesting. The user has to deal with the phpstan concern though.
     *
     * @throws RequestException
     */
    protected function getRequestBody(int $maxDepth): mixed
    {
        $content = $this->request->getContent();
        \Webmozart\Assert\Assert::string($content);
        try {
            return json_decode($content, true, $maxDepth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw RequestException::requestBody($content, $exception);
        }
    }
}
