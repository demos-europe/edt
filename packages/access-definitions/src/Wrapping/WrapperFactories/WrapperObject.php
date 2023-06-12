<?php

declare(strict_types=1);

namespace EDT\Wrapping\WrapperFactories;

use EDT\JsonApi\Schema\ContentField;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\AccessException;
use EDT\Wrapping\Contracts\PropertyAccessException;
use EDT\Wrapping\Contracts\Types\ExposableRelationshipTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use EDT\Wrapping\Properties\EntityVerificationTrait;
use InvalidArgumentException;
use Safe\Exceptions\PcreException;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function count;
use function Safe\preg_match;

/**
 * Wraps a given object, corresponding to a {@link TypeInterface}.
 *
 * Instances will provide read and write access to specific properties of the given object.
 *
 * Read access will only be granted if the given {@link TypeInterface} implements {@link TransferableTypeInterface}.
 * The properties allowed to be read depend on the return of {@link TransferableTypeInterface::getReadableProperties()}.
 * Only those relationships will be readable whose target type return `true` in
 * {@link ExposableRelationshipTypeInterface::isExposedAsRelationship()}
 *
 * Returned relationships will be wrapped themselves inside {@link WrapperObject} instances.
 *
 * @template TEntity of object
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class WrapperObject
{
    use EntityVerificationTrait;

    /**
     * @var non-empty-string
     */
    private const METHOD_PATTERN = '/(get|set)([A-Z_]\w*)/';

    /**
     * @param TEntity $entity
     * @param TransferableTypeInterface<TCondition, TSorting, TEntity> $type
     */
    public function __construct(
        private readonly object $entity,
        private readonly TransferableTypeInterface $type,
        private readonly WrapperObjectFactory $wrapperFactory
    ) {}

    /**
     * @return TypeInterface<TCondition, TSorting, TEntity>
     */
    public function getResourceType(): TypeInterface
    {
        return $this->type;
    }

    /**
     * @param non-empty-string $methodName
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed|null|void If no parameters given:<ul>
     *   <li>In case of a relationship: an array, {@link WrapperObject} or <code>null</code></li>
     *   <li>Otherwise a primitive type</li><li>If parameters given: `void`</li></ul>
     */
    public function __call(string $methodName, array $arguments = [])
    {
        [$access, $propertyName] = $this->parseMethodAccess($methodName);
        $argumentsCount = count($arguments);

        if ('get' === $access && 0 === $argumentsCount) {
            return $this->__get($propertyName);
        }
        if ('set' === $access && 1 === $argumentsCount) {
            $this->__set($propertyName, array_pop($arguments));

            return;
        }

        throw AccessException::unexpectedArguments($this->type, 0, $argumentsCount);
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return mixed|null The value of the accessed property. Each relationship entity will be  wrapped into another wrapper instance.
     */
    public function __get(string $propertyName)
    {
        // we allow reading of properties that are actually accessible
        $readableProperties = $this->type->getReadableProperties();

        // TODO: consider readability settings like default include and default field?

        if (array_key_exists($propertyName, $readableProperties[0])) {
            Assert::allKeyNotExists([$readableProperties[1], $readableProperties[2]], $propertyName);
            $readability = $readableProperties[0][$propertyName];

            return $readability->getValue($this->entity);
        }

        if (ContentField::ID === $propertyName) {
            return $this->type->getIdentifierReadability()->getValue($this->entity);
        }

        if (array_key_exists($propertyName, $readableProperties[1])) {
            Assert::allKeyNotExists([$readableProperties[0], $readableProperties[2]], $propertyName);
            $readability = $readableProperties[1][$propertyName];
            $relationshipType = $readability->getRelationshipType();
            $relationshipEntity = $readability->getValue($this->entity, []);
            if (null === $relationshipEntity) {
                return null;
            }

            return $this->wrapperFactory->createWrapper($relationshipEntity, $relationshipType);
        }

        if (array_key_exists($propertyName, $readableProperties[2])) {
            Assert::allKeyNotExists([$readableProperties[0], $readableProperties[1]], $propertyName);
            $readability = $readableProperties[2][$propertyName];
            $relationshipType = $readability->getRelationshipType();
            $relationshipEntities = $readability->getValue($this->entity, [], []);

            // wrap the entities
            return array_map(
                fn (object $entityToWrap) => $this->wrapperFactory->createWrapper($entityToWrap, $relationshipType),
                $relationshipEntities
            );
        }

        throw PropertyAccessException::propertyNotAvailableInReadableType(
            $propertyName,
            $this->type,
            ...array_keys(array_merge(...$readableProperties))
        );
    }

    /**
     * This method will prevent access to properties that should not be accessible.
     *
     * @param non-empty-string $propertyName
     * @param mixed $value The value to set. Will only be allowed if the property name matches with an allowed property
     *                     (must be {@link TransferableTypeInterface::getUpdatableProperties() updatable}.
     *
     * @throws AccessException
     */
    public function __set(string $propertyName, mixed $value): void
    {
        try {
            $updatabilities = $this->type->getUpdatableProperties();

            if (array_key_exists($propertyName, $updatabilities[0])) {
                Assert::allKeyNotExists([$updatabilities[1], $updatabilities[2]], $propertyName);
                $updatabilities[0][$propertyName]->updateAttributeValue($this->entity, $value);

                return;
            }

            if (array_key_exists($propertyName, $updatabilities[1])) {
                Assert::allKeyNotExists([$updatabilities[0], $updatabilities[2]], $propertyName);
                $updatability = $updatabilities[1][$propertyName];
                $relationshipClass = $updatability->getRelationshipType()->getEntityClass();
                $relationship = $this->assertValidToOneValue($value, $relationshipClass);
                $updatability->updateToOneRelationship($this->entity, $relationship);

                return;
            }

            if (array_key_exists($propertyName, $updatabilities[2])) {
                Assert::allKeyNotExists([$updatabilities[0], $updatabilities[1]], $propertyName);
                $updatability = $updatabilities[2][$propertyName];
                $relationshipClass = $updatability->getRelationshipType()->getEntityClass();
                $relationships = $this->assertValidToManyValue($value, $relationshipClass);
                $updatability->updateToManyRelationship($this->entity, $relationships);

                return;
            }
        } catch (\Exception $exception) {
            throw PropertyAccessException::update($propertyName, $exception);
        }

        throw PropertyAccessException::propertyNotAvailableInUpdatableType($propertyName, $this->type, ...array_keys(array_merge(...$updatabilities)));
    }

    /**
     * @param non-empty-string $propertyName
     */
    public function getPropertyValue(string $propertyName): mixed
    {
        return $this->__get($propertyName);
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @throws AccessException
     */
    public function setPropertyValue(string $propertyName, mixed $value): void
    {
        $this->__set($propertyName, $value);
    }

    /**
     * Expects a getter or setter property name (e.g. `getFoo`/`setFoo`). Splits method name into
     * the `get` or `set` part and the upper-cased property name.
     *
     * @param non-empty-string $methodName
     *
     * @return array{0: 'set'|'get', 1: non-empty-string}
     *
     * @throws AccessException
     * @throws InvalidArgumentException
     */
    protected function parseMethodAccess(string $methodName): array
    {
        try {
            preg_match(self::METHOD_PATTERN, $methodName, $match);
        } catch (PcreException $exception) {
            throw new InvalidArgumentException($methodName, 0, $exception);
        }
        Assert::isArray($match);
        Assert::count($match, 3);
        Assert::keyExists($match, 1);
        Assert::keyExists($match, 2);

        $propertyName = $match[2];
        Assert::string($propertyName);
        $access = $match[1];
        $propertyName = lcfirst($propertyName);

        if ('' === $propertyName || ('get' !== $access && 'set' !== $access)) {
            throw AccessException::failedToParseAccessor($this->type, $methodName);
        }

        return [$access, $propertyName];
    }

    /**
     * @return TEntity
     *
     * @internal Warning: exposing the backing object is dangerous, as it allows to read values
     * unrestricted not only from the returned object but all its relationships.
     *
     * @deprecated use {@link self::getEntity} instead
     */
    public function getObject(): object
    {
        return $this->entity;
    }

    /**
     * @return TEntity
     *
     * @internal Warning: exposing the backing object is dangerous, as it allows to read values
     * unrestricted not only from the returned object but all its relationships.
     */
    public function getEntity(): object
    {
        return $this->entity;
    }
}
