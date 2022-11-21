<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Querying\Contracts\FunctionInterface;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\Utilities\Iterables;
use EDT\Wrapping\Properties\AbstractRelationshipReadability;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;
use EDT\Wrapping\WrapperFactories\WrapperObject;
use EDT\Wrapping\WrapperFactories\WrapperObjectFactory;
use League\Fractal\ParamBag;
use Safe\Exceptions\StringsException;
use Webmozart\Assert\Assert;
use function get_class;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use function array_key_exists;
use function count;
use function in_array;
use InvalidArgumentException;
use function Safe\substr;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;
use Psr\Log\LoggerInterface;

/**
 * Behavior can be configured on instantiation.
 *
 * This transformer takes a {@link ResourceTypeInterface} instance and uses the
 * {@link ResourceTypeInterface::getReadableResourceTypeProperties()} to transform given entities
 * corresponding to that resource type.
 *
 * For example if only a single attribute the 'title' is defined (and
 * set as default) then this transformer will transform the given entity into a format only
 * containing this single attribute. If additionally a single relationship include is
 * defined but not marked as default then the behavior stays the same, unless in the request it is
 * explicitly stated that that include should be part of the transformer result too.
 *
 * If the given {@link ResourceTypeInterface} is mis-configured, e.g. contains readable properties
 * that do not exist in the entity to be transformed, then the behavior is undefined.
 *
 * @template TCondition of \EDT\Querying\Contracts\FunctionInterface<bool>
 * @template TSorting of \EDT\Querying\Contracts\SortMethodInterface
 * @template TEntity of object
 */
class DynamicTransformer extends TransformerAbstract
{
    private const ID = 'id';

    /**
     * @var ResourceTypeInterface<FunctionInterface<bool>, SortMethodInterface, TEntity>
     */
    private ResourceTypeInterface $type;

    /**
     * @var array<non-empty-string, AttributeReadability<TEntity>>
     */
    private array $attributeReadabilities;

    private ?LoggerInterface $logger;

    private MessageFormatter $messageFormatter;

    private WrapperObjectFactory $wrapperFactory;

    /**
     * @var array<non-empty-string, ToOneRelationshipReadability<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
     */
    private array $toOneRelationshipReadabilities;

    /**
     * @var array<non-empty-string, ToManyRelationshipReadability<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>>>
     */
    private array $toManyRelationshipReadabilities;

    /**
     * @param ResourceTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        ResourceTypeInterface $type,
        WrapperObjectFactory $wrapperFactory,
        MessageFormatter $messageFormatter,
        ?LoggerInterface $logger
    ) {
        $this->type = $type;
        $readableProperties = $type->getReadableResourceTypeProperties();
        [
            $this->attributeReadabilities,
            $this->toOneRelationshipReadabilities,
            $this->toManyRelationshipReadabilities
        ] = $readableProperties;
        $this->logger = $logger;
        $this->messageFormatter = $messageFormatter;

        if (!array_key_exists(self::ID, $this->attributeReadabilities)) {
            throw new InvalidArgumentException('An attribute definition for the `id` is required, as it is needed by Fractal');
        }

        $relationshipReadabilities = array_merge(
            $this->toOneRelationshipReadabilities,
            $this->toManyRelationshipReadabilities
        );

        $this->setAvailableIncludes(array_keys($relationshipReadabilities));
        $this->setDefaultIncludes(array_keys(array_filter(
            $relationshipReadabilities,
            static fn (AbstractRelationshipReadability $readability): bool => $readability->isDefaultInclude()))
        );
        $this->wrapperFactory = $wrapperFactory;
    }

    /**
     * If no specific fields were requested the attributes marked as defaults will be returned. If
     * a specific set of fields was requested only attributes in that set will be returned.
     *
     * @param TEntity $entity
     *
     * @return array<string, mixed>
     *
     * @throws TransformException
     */
    public function transform($entity): array
    {
        Assert::isInstanceOf($entity, $this->type->getEntityClass());

        $effectiveReadabilities = $this->getEffectiveAttributeReadabilities($this->attributeReadabilities);

        $attributesToReturn = [];
        foreach ($effectiveReadabilities as $attributeName => $readability) {
            $customReadCallable = $readability->getCustomValueFunction();
            if (null !== $customReadCallable) {
                $attributesToReturn[$attributeName] = $customReadCallable($entity);
            }

            // we should only get non-objects here, so there is no need to unwrap
            $attributeValue = $this->getValueViaWrapper($entity, $attributeName);
            if (null !== $attributeValue
                && !is_string($attributeValue)
                && !is_int($attributeValue)
                && !is_float($attributeValue)
                && !is_bool($attributeValue)
                && !is_array($attributeValue) // TODO: validate array content further?
            ) {
                throw TransformException::nonAttributeValue(gettype($attributeValue));
            }
            $attributesToReturn[$attributeName] = $attributeValue;
        }

        return $attributesToReturn;
    }

    /**
     * @param non-empty-string $methodName
     * @param array{0: TEntity, 1: ParamBag} $arguments
     *
     * @return Collection|Item|NullResource
     *
     * @throws TransformException
     */
    public function __call(string $methodName, array $arguments): ResourceAbstract
    {
        Assert::stringNotEmpty($methodName);
        Assert::count($arguments, 2);
        Assert::keyExists($arguments, 0);
        Assert::keyExists($arguments, 1);

        [$entity, $paramBag] = $arguments;

        Assert::isInstanceOf($entity, $this->type->getEntityClass());
        Assert::isInstanceOf($paramBag, ParamBag::class);

        $includeName = $this->getIncludeName($methodName);
        Assert::stringNotEmpty($includeName);

        if (array_key_exists($includeName, $this->toOneRelationshipReadabilities)) {
            $relationshipReadability = $this->toOneRelationshipReadabilities[$includeName];

            return $this->handleToOneRelationship($relationshipReadability, $entity, $includeName);
        } elseif (array_key_exists($includeName, $this->toManyRelationshipReadabilities)) {
            $relationshipReadability = $this->toManyRelationshipReadabilities[$includeName];

            return $this->handleToManyRelationship($relationshipReadability, $entity, $includeName);
        } else {
            throw TransformException::includeNotAvailable($includeName);
        }
    }

    /**
     * @param ToOneRelationshipReadability<TCondition, TSorting, TEntity, object,  ResourceTypeInterface<TCondition, TSorting, object>> $readability
     * @param TEntity                        $entity
     * @param non-empty-string               $includeName
     *
     * @return Item|NullResource
     */
    protected function handleToOneRelationship(
        ToOneRelationshipReadability $readability,
        object $entity,
        string $includeName
    ): ResourceAbstract {
        $relationshipType = $readability->getRelationshipType();

        $customReadCallable = $readability->getCustomValueFunction();
        if (null !== $customReadCallable) {
            $value = $customReadCallable($entity);
        } else {
            $value = $this->getValueViaWrapper($entity, $includeName);
            $entityClass = $relationshipType->getEntityClass();

            if ($value instanceof WrapperObject) {
                $value = $value->getObject();
            } elseif (null !== $value && !$value instanceof $entityClass) {
                throw TransformException::nonToOneType(gettype($value), $entityClass);
            }
        }

        return null === $value
            ? $this->null()
            : new Item($value, $relationshipType->getTransformer(), $relationshipType::getName());
    }

    /**
     * @param ToManyRelationshipReadability<TCondition, TSorting, TEntity, object, ResourceTypeInterface<TCondition, TSorting, object>> $readability
     * @param TEntity                         $entity
     * @param non-empty-string                $includeName
     *
     * @return Collection
     */
    protected function handleToManyRelationship(
        ToManyRelationshipReadability $readability,
        object $entity,
        string $includeName
    ): Collection {
        $relationshipType = $readability->getRelationshipType();

        $customReadCallable = $readability->getCustomValueFunction();
        if (null !== $customReadCallable) {
            $values = $customReadCallable($entity);
        } else {
            $values = $this->getValueViaWrapper($entity, $includeName);
            if (!is_iterable($values)) {
                throw TransformException::nonToManyIterable(gettype($values));
            }

            $entityClass = $relationshipType->getEntityClass();
            $values = array_map(
                static function (WrapperObject $object) use ($entityClass): object {
                    $value = $object->getObject();
                    if (!$value instanceof $entityClass) {
                        throw TransformException::nonToManyNestedType(get_class($value), $entityClass);
                    }

                    return $value;
                },
                array_values(Iterables::asArray($values))
            );
        }

        return new Collection($values, $relationshipType->getTransformer(), $relationshipType::getName());
    }

    /**
     * The application will pass raw entities into the transformer. This method will automatically
     * wrap it to check authorizations when retrieving the property value via the wrapper.
     *
     * Note that because the next transformer may require the actual entity instance instead
     * of the wrapper you need to unwrap returned {@link WrapperObject} instances.
     *
     * The alternative would be
     * to either adjust all parameter types in the transformers to accept {@link WrapperObject}
     * or to dynamically extend {@link WrapperObject} from the current entity class via eval().
     *
     * @param TEntity $entity
     * @param non-empty-string $propertyName
     *
     * @return mixed|null
     */
    protected function getValueViaWrapper(object $entity, string $propertyName)
    {
        $entity = $this->wrapperFactory->createWrapper($entity, $this->type);
        return $entity->getPropertyValue($propertyName);
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
     * @throws ExcludeException
     */
    public function processIncludedResources(Scope $scope, $data)
    {
        $this->validateExcludes($scope);
        $this->validateIncludes($scope);

        return parent::processIncludedResources($scope, $data);
    }

    /**
     * @throws TransformException
     */
    private function getIncludeName(string $includeMethodName): string
    {
        if (0 !== strncmp($includeMethodName, 'include', 7)) {
            throw TransformException::noIncludeMethod($includeMethodName);
        }

        try {
            return lcfirst(substr($includeMethodName, 7));
        } catch (StringsException $exception) {
            throw TransformException::substring($exception);
        }
    }

    /**
     * @throws ExcludeException
     */
    private function validateExcludes(Scope $scope): void
    {
        $requestedExcludes = $scope->getManager()->getRequestedExcludes();
        $requestedExcludesCount = count($requestedExcludes);
        if (1 < $requestedExcludesCount || (1 === $requestedExcludesCount && '' !== $requestedExcludes[0])) {
            throw ExcludeException::notAllowed();
        }
    }

    public function validateIncludes(Scope $scope): void
    {
        $requestedIncludes = $scope->getManager()->getRequestedIncludes();
        $notAvailableIncludes = [];
        foreach ($requestedIncludes as $requestedInclude) {
            if (!$scope->isRequested($requestedInclude)) {
                // continue if the include was not requested for this specific type
                continue;
            }
            $requestedIncludePath = explode('.', $requestedInclude);
            $firstSegment = array_shift($requestedIncludePath);
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
     * @param list<string> $notAvailableIncludes
     */
    private function createIncludeErrorMessage(array $notAvailableIncludes): string
    {
        $notAvailableIncludesString = $this->messageFormatter->propertiesToString($notAvailableIncludes);
        $message = "The following requested includes are not available in the resource type '{$this->type::getName()}': $notAvailableIncludesString.";

        if ([] !== $this->availableIncludes) {
            $availableIncludesString = $this->messageFormatter->propertiesToString($this->availableIncludes);
            $message .= " Available includes are: $availableIncludesString.";
        } else {
            $message .= ' No includes are available.';
        }

        return $message;
    }

    /**
     * @param array<non-empty-string, AttributeReadability<TEntity>> $attributeReadabilities
     *
     * @return array<non-empty-string, AttributeReadability<TEntity>>
     *
     * @throws TransformException
     */
    protected function getEffectiveAttributeReadabilities(array $attributeReadabilities): array
    {
        $scope = $this->getCurrentScope();
        if (null === $scope) {
            throw TransformException::nullScope();
        }
        $fieldsetBag = $scope->getManager()->getFieldset($this->type::getName());
        if (null === $fieldsetBag) {
            // default attribute fields
            return array_filter(
                $attributeReadabilities,
                static fn (AttributeReadability $readability, string $attributeName): bool =>
                    // always keep the 'id` attribute, it is required by Fractal
                    self::ID === $attributeName
                    // keep the attributes that are to be returned by default
                    || $readability->isDefaultField(),
                ARRAY_FILTER_USE_BOTH
            );
        } else {
            // requested attribute fields
            $fieldset = Iterables::asArray($fieldsetBag);
            return array_filter(
                $attributeReadabilities,
                fn (string $attributeName): bool =>
                    // always keep the 'id` attribute, it is required by Fractal
                    self::ID === $attributeName
                    // keep the attributes that were requested
                    || in_array($attributeName, $fieldset, true),
                ARRAY_FILTER_USE_KEY
            );
        }
    }
}
