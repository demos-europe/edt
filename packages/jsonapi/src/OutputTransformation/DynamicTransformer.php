<?php

declare(strict_types=1);

namespace EDT\JsonApi\OutputTransformation;

use EDT\JsonApi\RequestHandling\MessageFormatter;
use EDT\Querying\Utilities\Iterables;
use Safe\Exceptions\StringsException;
use function gettype;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use function array_key_exists;
use function count;
use function in_array;
use InvalidArgumentException;
use function Safe\substr;
use function is_object;
use League\Fractal\ParamBag;
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
 * This transformer can accept any kind of entity in array or object format and will transform
 * it is defined in the {@link PropertyDefinitionInterface attribute} and
 * {@link IncludeDefinitionInterface include} definitions given on instantiation.
 *
 * For example if only a single attribute definition for the 'title' attribute is given (and
 * set as default) then this transformer will transform the given entity into a format only
 * containing this single attribute. If additionally a single relationship include definition is
 * given but not marked as default then the behavior stays the same, unless in the request it is
 * explicitly stated that that include should be part of the transformer result too.
 *
 * If the transformer is mis-configured, e.g. definitions for properties are given that do not exist
 * in the entity to be transformed, then the behavior is undefined.
 *
 * @template TEntity of object
 */
class DynamicTransformer extends TransformerAbstract
{
    private const ID = 'id';

    /**
     * @var non-empty-string
     */
    private string $type;

    /**
     * @var array<non-empty-string, IncludeDefinitionInterface>
     */
    private array $includeDefinitions;

    /**
     * @var array<non-empty-string, PropertyDefinitionInterface>
     */
    private array $attributeDefinitions;

    private ?LoggerInterface $logger = null;

    private MessageFormatter $messageFormatter;

    /**
     * @param non-empty-string                                                     $type
     * @param array<non-empty-string, PropertyDefinitionInterface<TEntity, mixed>> $attributeDefinitions mappings from an
     *                                                                                   attribute name to its
     *                                                                                   definition
     * @param array<non-empty-string, IncludeDefinitionInterface<TEntity, object>> $includeDefinitions   mappings from an
     *                                                                                   include name to its
     *                                                                                   definition
     */
    public function __construct(
        string $type,
        array $attributeDefinitions,
        array $includeDefinitions,
        MessageFormatter $messageFormatter,
        ?LoggerInterface $logger
    ) {
        $this->type = $type;
        $this->includeDefinitions = $includeDefinitions;
        $this->attributeDefinitions = $attributeDefinitions;
        if (!array_key_exists(self::ID, $this->attributeDefinitions)) {
            throw new InvalidArgumentException('An attribute definition for the `id` is required, as it is needed by Fractal');
        }
        $this->setAvailableIncludes(array_keys($includeDefinitions));
        $this->setDefaultIncludes(
            array_keys(array_filter($includeDefinitions, [$this, 'isDefaultInclude']))
        );
        $this->logger = $logger;
        $this->messageFormatter = $messageFormatter;
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
        if (!is_object($entity)) {
            $actualType = gettype($entity);
            throw new InvalidArgumentException("Expected object, got $actualType when trying to transform into `$this->type`.");
        }
        $paramBag = new ParamBag([]);
        $scope = $this->getCurrentScope();
        if (null === $scope) {
            throw new TransformException('Scope was unexpectedly null.');
        }
        $fieldsetBag = $scope->getManager()->getFieldset($this->type);
        if (null === $fieldsetBag) {
            // default definitions
            $definitions = array_filter(
                $this->attributeDefinitions,
                static fn (PropertyDefinitionInterface $definition, string $attributeName): bool =>
                    // always keep the 'id` attribute, it is required by Fractal
                    self::ID === $attributeName
                    // keep the attributes that are to be returned by default
                    || $definition->isToBeUsedAsDefaultField(),
                ARRAY_FILTER_USE_BOTH
            );
        } else {
            // requested definitions
            $fieldset = Iterables::asArray($fieldsetBag);
            $definitions = array_filter(
                $this->attributeDefinitions,
                fn (string $attributeName): bool =>
                    // always keep the 'id` attribute, it is required by Fractal
                    self::ID === $attributeName
                    // keep the attributes that were requested
                    || $this->isAttributeRequested($attributeName, $fieldset),
                ARRAY_FILTER_USE_KEY
            );
        }

        return array_map(
            static fn (PropertyDefinitionInterface $definition) => $definition->determineData($entity, $paramBag),
            $definitions
        );
    }

    /**
     * @param array<mixed, mixed> $arguments We expect the include-target (the entity to transform)
     *                                       to be the first value and a
     *                                       {@link \League\Fractal\ParamBag} as second parameter.
     *                                       Otherwise, an {@link InvalidArgumentException} is
     *                                       thrown.
     *
     * @return Collection|Item|NullResource
     *
     * @throws InvalidArgumentException
     */
    public function __call(string $methodName, array $arguments): ResourceAbstract
    {
        if (2 !== count($arguments)) {
            throw new InvalidArgumentException('Invalid parameter count');
        }

        $includeName = $this->getIncludeName($methodName);
        $includeDefinition = $this->getIncludeDefinition($includeName);

        $data = $includeDefinition->determineData($arguments[0], $arguments[1]);
        if (null === $data) {
            return $this->null();
        }

        $params = [
            $data,
            $includeDefinition->getTransformer(),
            $includeDefinition->getResourceKey(),
        ];

        return $includeDefinition->isToMany($data)
            ? new Collection(...$params)
            : new Item(...$params);
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
     * @param string[] $fieldset
     */
    private function isAttributeRequested(string $attributeName, array $fieldset): bool
    {
        return in_array($attributeName, $fieldset, true);
    }

    /**
     * @throws StringsException
     * @throws InvalidArgumentException
     */
    private function getIncludeName(string $includeMethodName): string
    {
        if (0 !== strncmp($includeMethodName, 'include', 7)) {
            throw new InvalidArgumentException('No such method exists');
        }

        return lcfirst(substr($includeMethodName, 7));
    }

    /**
     * @return IncludeDefinitionInterface
     */
    private function getIncludeDefinition(string $includeName): IncludeDefinitionInterface
    {
        if (!array_key_exists($includeName, $this->includeDefinitions)) {
            throw new InvalidArgumentException("Include '$includeName' is not available");
        }

        return $this->includeDefinitions[$includeName];
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

    private function isDefaultInclude(IncludeDefinitionInterface $definition): bool
    {
        return $definition->isToBeUsedAsDefaultInclude();
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
        $message = "The following requested includes are not available in the resource type '$this->type': $notAvailableIncludesString.";

        if ([] !== $this->availableIncludes) {
            $availableIncludesString = $this->messageFormatter->propertiesToString($this->availableIncludes);
            $message .= " Available includes are: $availableIncludesString.";
        } else {
            $message .= ' No includes are available.';
        }

        return $message;
    }
}
