<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputHandling;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Contracts\ContentField;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\PropertyBehavior\Attribute\AttributeReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\IdAttributeConflictException;
use EDT\Wrapping\PropertyBehavior\Identifier\IdentifierReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\RelationshipReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToMany\ToManyRelationshipReadabilityInterface;
use EDT\Wrapping\PropertyBehavior\Relationship\ToOne\ToOneRelationshipReadabilityInterface;
use Exception;
use InvalidArgumentException;
use League\Fractal\ParamBag;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function count;
use function in_array;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;

/**
 * This transformer takes a {@link TransferableTypeInterface} instance and uses the
 * {@link TransferableTypeInterface::getReadability() readable properties} to transform given
 * entities corresponding to that type.
 *
 * For example if only a single attribute 'title' is defined (and set as default) then this
 * transformer will transform the given entity into a format only containing this single attribute.
 * If additionally a single relationship include is defined but not marked as default then the
 * behavior stays the same, unless in the request it is explicitly stated this include should be
 * part of the transformer result too.
 *
 * If the given {@link TransferableTypeInterface} is mis-configured, e.g. contains readable
 * properties that do not exist in the entity to be transformed, then the behavior is undefined.
 *
 * @template TEntity of object
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class DynamicTransformer extends TransformerAbstract
{
    /**
     * @var array<non-empty-string, AttributeReadabilityInterface<TEntity>>
     */
    private array $attributeReadabilities;

    /**
     * @var array<non-empty-string, ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>>
     */
    private array $toOneRelationshipReadabilities;

    /**
     * @var array<non-empty-string, ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object>>
     */
    private array $toManyRelationshipReadabilities;

    /**
     * @var IdentifierReadabilityInterface<TEntity>
     */
    private IdentifierReadabilityInterface $idReadability;

    /**
     * @param TransferableTypeInterface<TCondition, TSorting, TEntity> $type
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        protected readonly TransferableTypeInterface $type,
        protected readonly MessageFormatter $messageFormatter,
        protected readonly ?LoggerInterface $logger
    ) {
        $readabilityCollection = $this->type->getReadability();
        $this->attributeReadabilities = $readabilityCollection->getAttributes();
        $this->toOneRelationshipReadabilities = $readabilityCollection->getToOneRelationships();
        $this->toManyRelationshipReadabilities = $readabilityCollection->getToManyRelationships();
        $this->idReadability = $readabilityCollection->getIdentifierReadability();

        if (array_key_exists(ContentField::ID, $this->attributeReadabilities)) {
            throw IdAttributeConflictException::create($type->getTypeName());
        }

        $relationshipReadabilities = array_merge(
            $this->toOneRelationshipReadabilities,
            $this->toManyRelationshipReadabilities
        );

        $this->setAvailableIncludes(array_keys($relationshipReadabilities));
        $this->setDefaultIncludes(array_keys(array_filter(
            $relationshipReadabilities,
            static fn (RelationshipReadabilityInterface $readability): bool => $readability->isDefaultInclude()))
        );
    }

    /**
     * Reads the attribute values via the {@link self::$attributeReadabilities}.
     *
     * If no specific fields were requested the attributes marked as defaults will be returned. If
     * a specific set of fields was requested only attributes in that set will be returned.
     *
     * @param TEntity $entity
     *
     * @return array<non-empty-string, mixed>
     *
     * @throws InvalidArgumentException unexpected scope or entity given
     * @throws PropertyTransformException transforming a specific attribute failed
     */
    public function transform($entity): array
    {
        $scope = $this->getCurrentScope();
        Assert::notNull($scope);
        Assert::isInstanceOf($entity, $this->type->getEntityClass());

        $effectiveAttributes = $this->getEffectiveAttributeReadabilities($scope);
        $effectiveAttributes[ContentField::ID] = $this->idReadability;

        $resultAttributes = [];
        foreach ($effectiveAttributes as $attributeName => $readability) {
            try {
                $resultAttributes[$attributeName] = $readability->getValue($entity);
            } catch (Exception $exception) {
                throw new PropertyTransformException($attributeName, $exception);
            }
        }

        return $resultAttributes;
    }

    /**
     * @param non-empty-string $methodName
     * @param array{0: TEntity, 1: ParamBag} $arguments
     *
     * @throws InvalidArgumentException include name could not be determined
     * @throws PropertyTransformException transforming a specific relationship failed
     *
     * @see callIncludeMethod
     */
    public function __call(string $methodName, array $arguments): Collection|Item|NullResource
    {
        Assert::stringNotEmpty($methodName);
        Assert::startsWith($methodName, 'include');
        $includeName = lcfirst(substr($methodName, 7));
        Assert::stringNotEmpty($includeName);

        try {
            Assert::count($arguments, 2);
            Assert::keyExists($arguments, 0);
            Assert::keyExists($arguments, 1);

            [$entity, $paramBag] = $arguments;

            Assert::isInstanceOf($entity, $this->type->getEntityClass());
            Assert::isInstanceOf($paramBag, ParamBag::class);

            if (array_key_exists($includeName, $this->toOneRelationshipReadabilities)) {
                $relationshipReadability = $this->toOneRelationshipReadabilities[$includeName];
                Assert::keyNotExists($this->toManyRelationshipReadabilities, $includeName);

                return $this->retrieveRelationshipItem($relationshipReadability, $entity);
            }

            if (array_key_exists($includeName, $this->toManyRelationshipReadabilities)) {
                $relationshipReadability = $this->toManyRelationshipReadabilities[$includeName];
                Assert::keyNotExists($this->toOneRelationshipReadabilities, $includeName);

                return $this->retrieveRelationshipCollection($relationshipReadability, $entity);
            }

            throw new InvalidArgumentException("Include '$includeName' is not available");
        } catch (Exception $exception) {
            throw new PropertyTransformException($includeName, $exception);
        }
    }

    /**
     * @param ToOneRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object> $readability
     * @param TEntity $entity
     *
     * @throws InvalidArgumentException
     */
    protected function retrieveRelationshipItem(
        ToOneRelationshipReadabilityInterface $readability,
        object $entity
    ): Item|NullResource {
        $relationshipEntity = $readability->getValue($entity, []);
        if (null === $relationshipEntity) {
            return $this->null();
        }
        $relationshipType = $readability->getRelationshipType();
        $transformer = $this->createRelationshipTransformer($relationshipType);

        return $this->item($relationshipEntity, $transformer, $relationshipType->getTypeName());
    }

    /**
     * @param ToManyRelationshipReadabilityInterface<TCondition, TSorting, TEntity, object> $readability
     * @param TEntity $entity
     *
     * @throws InvalidArgumentException
     */
    protected function retrieveRelationshipCollection(
        ToManyRelationshipReadabilityInterface $readability,
        object $entity
    ): Collection {
        $relationshipEntities = $readability->getValue($entity, [], []);
        $relationshipType = $readability->getRelationshipType();
        $transformer = $this->createRelationshipTransformer($relationshipType);

        return $this->collection($relationshipEntities, $transformer, $relationshipType->getTypeName());
    }

    /**
     * @param TransferableTypeInterface<TCondition, TSorting, object> $relationshipType
     *
     * @throws InvalidArgumentException
     */
    protected function createRelationshipTransformer(TransferableTypeInterface $relationshipType): TransformerAbstract
    {
        return new DynamicTransformer(
            $relationshipType,
            $this->messageFormatter,
            $this->logger
        );
    }

    /**
     * Works like the overridden method but throws an exception if excludes are requested
     * and logs when non-available includes are requested.
     *
     * Excluding relationships would work but is not allowed for now because the syntax is not
     * defined in the JSON:API and we want to discourage clients to use a syntax that may change
     * in the future to avoid having to deal with backward compatibility.
     *
     * At least logging when non-available includes are requested is better in our case than
     * the default Fractal behavior to silently not include them, because the page will not work
     * and with the log message the cause is way more clear.
     *
     * @throws InvalidArgumentException
     */
    public function processIncludedResources(Scope $scope, mixed $data): array|false
    {
        $this->validateExcludes($scope);
        $this->validateIncludes($scope);

        return parent::processIncludedResources($scope, $data);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateExcludes(Scope $scope): void
    {
        $requestedExcludes = $scope->getManager()->getRequestedExcludes();
        $requestedExcludesCount = count($requestedExcludes);
        if (1 < $requestedExcludesCount || (1 === $requestedExcludesCount && '' !== $requestedExcludes[0])) {
            throw new InvalidArgumentException('Excluding relationships is not supported.');
        }
    }

    /**
     * TODO: by validating the request before invoking the root transformer the warning logging in this class can be replaced with an exception, as it should never occur
     *
     * @throws InvalidArgumentException
     */
    public function validateIncludes(Scope $scope): void
    {
        $requestedIncludes = $scope->getManager()->getRequestedIncludes();
        $notAvailableIncludes = [];
        foreach ($requestedIncludes as $requestedInclude) {
            Assert::stringNotEmpty($requestedInclude);
            if (!$scope->isRequested($requestedInclude)) {
                // continue if the include was not requested for this specific type
                continue;
            }
            $requestedIncludePath = explode('.', $requestedInclude);
            $firstSegment = array_shift($requestedIncludePath);
            Assert::stringNotEmpty($firstSegment);
            if (!in_array($firstSegment, $this->availableIncludes, true)) {
                $notAvailableIncludes[] = $firstSegment;
            }
        }

        if ([] !== $notAvailableIncludes && null !== $this->logger) {
            $message = $this->createIncludeErrorMessage($notAvailableIncludes);
            $this->logger->warning($message);
        }
    }

    /**
     * @param non-empty-list<non-empty-string> $notAvailableIncludes
     */
    protected function createIncludeErrorMessage(array $notAvailableIncludes): string
    {
        $notAvailableIncludesString = $this->messageFormatter->propertiesToString($notAvailableIncludes);
        $message = "The following requested includes are not available in the resource type '{$this->type->getTypeName()}': $notAvailableIncludesString.";

        if ([] !== $this->availableIncludes) {
            $availableIncludesString = $this->messageFormatter->propertiesToString($this->availableIncludes);
            $message .= " Available includes are: $availableIncludesString.";
        } else {
            $message .= ' No includes are available.';
        }

        return $message;
    }

    /**
     * Get only those attributes from {@link self::$attributes} that are relevant for the response.
     *
     * I.e. if specific fields were requested return only the corresponding attributes for those.
     * If no specific fields were requested, return only the attributes that are set as default
     * field.
     *
     * @return array<non-empty-string, AttributeReadabilityInterface<TEntity>>
     */
    protected function getEffectiveAttributeReadabilities(Scope $scope): array
    {
        $fieldsetBag = $scope->getManager()->getFieldset($this->type->getTypeName());
        if (null === $fieldsetBag) {
            // if no fieldset was requested, return default attribute fields
            return array_filter(
                $this->attributeReadabilities,
                static fn (AttributeReadabilityInterface $readability, string $attributeName): bool => $readability->isDefaultField(),
                ARRAY_FILTER_USE_BOTH
            );
        }

        // requested attribute fields
        $fieldset = iterator_to_array($fieldsetBag);
        return array_filter(
            $this->attributeReadabilities,
            static fn (string $attributeName): bool => in_array($attributeName, $fieldset, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
