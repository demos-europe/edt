<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use ArrayIterator;
use EDT\Parsing\Utilities\ParseException;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\AliasableTypeInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use Exception;
use ReflectionClass;
use Safe\Exceptions\ArrayException;
use function array_key_exists;
use function get_class;
use function Safe\array_combine;

/**
 * Denotes a path usable in a condition that can be finalized to a string in dot-notation.
 *
 * Classes using this trait can define one or multiple <code>property-read</code> annotations with a
 * type implementing {@link PropertyPathInterface}. These can be accessed from the outside like normal (public)
 * properties and will return an instance of their return type with the current instance set as
 * parent. This allows the user to build a path by starting with a type and descending into its
 * relationships. When the desired relationship is reached it can be converted into an array
 * {@link PropertyAutoPathTrait::getAsNames() array} or {@link PropertyAutoPathTrait::getAsNamesInDotNotation() string}.
 *
 * You can optionally implement {@link IteratorAggregate} in the class using this trait without the
 * need to add any methods.
 */
trait PropertyAutoPathTrait
{
    private ?PropertyAutoPathInterface $parent = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $parentPropertyName = null;

    /**
     * Will be used instead of {@link PropertyAutoPathTrait::createChild()} when set to non-`null`.
     * @var callable(string, PropertyPathInterface, string): PropertyPathInterface
     */
    private $childCreateCallback;

    /**
     * `null` if no parsing happened yet.
     *
     * @var array<non-empty-string, class-string>|null
     */
    private ?array $properties = null;

    /**
     * @var array<non-empty-string, PropertyPathInterface>
     */
    private array $children = [];

    /**
     * Will be initialized when {@link PropertyAutoPathTrait::getDocblockTraitEvaluator()} is called.
     */
    private ?DocblockPropertyByTraitEvaluator $docblockTraitEvaluator = null;

    /**
     * @param mixed ...$constructorArgs
     * @return static
     * @throws PathBuildException
     */
    public static function startPath(...$constructorArgs): self
    {
        $implementingClass = static::class;
        if (!is_subclass_of($implementingClass, PropertyAutoPathInterface::class)) {
            throw PathBuildException::missingInterface(static::class, PropertyAutoPathInterface::class);
        }

        try {
            return static::createChild($implementingClass, null, null, $constructorArgs);
        } catch (Exception $exception) {
            throw PathBuildException::startPathFailed(static::class, $exception);
        }
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @throws PathBuildException
     */
    public function __get(string $propertyName): PropertyPathInterface
    {
        $implementingClass = static::class;
        if (!is_subclass_of($implementingClass, PropertyAutoPathInterface::class)) {
            throw PathBuildException::missingInterface(static::class, PropertyAutoPathInterface::class);
        }

        // if we already created the child we avoid creating it again, as for each created child
        // the properties of the target will be parsed, even if the target type was parsed before
        if (!array_key_exists($propertyName, $this->children)) {
            try {
                $properties = $this->getAutoPathProperties();
                if (!array_key_exists($propertyName, $properties)) {
                    throw PathBuildException::createFromName($propertyName, static::class, ...$this->docblockTraitEvaluator->getTargetTags());
                }

                $returnType = $properties[$propertyName];

                $this->children[$propertyName] = null === $this->childCreateCallback
                    ? self::createChild($returnType, $this, $propertyName)
                    : ($this->childCreateCallback)($returnType, $this, $propertyName);
            } catch (ParseException $e) {
                throw PathBuildException::getPropertyFailed(static::class, $propertyName, $e);
            } catch (Exception $e) {
                throw PathBuildException::genericCreateChild(get_class($this), $propertyName, $e);
            }
        }

        return $this->children[$propertyName];
    }

    /**
     * @internal
     */
    public function setParent(PropertyAutoPathInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @param non-empty-string $parentPropertyName
     *
     * @internal
     */
    public function setParentPropertyName(string $parentPropertyName): void
    {
        $this->parentPropertyName = $parentPropertyName;
    }

    /**
     * @see PropertyPathInterface::getAsNamesInDotNotation()
     */
    public function getAsNamesInDotNotation(): string
    {
        return implode('.', $this->getAsNames());
    }

    /**
     * Returns the instances that are part of this path.
     *
     * @return non-empty-list<PropertyAutoPathInterface> All objects that are part of this path, including the starting object without corresponding
     * property name.
     */
    public function getAsValues(): array
    {
        $path = [];
        if (null !== $this->parent) {
            $path = $this->parent->getAsValues();
        }
        $path[] = $this;
        return $path;
    }

    /**
     * @return non-empty-list<non-empty-string>
     *
     * @see PropertyPathInterface::getAsNames()
     *
     * @throws PathException
     */
    public function getAsNames(): array
    {
        $path = [];
        if (null !== $this->parent && 0 !== $this->parent->getCount()) {
            $path = $this->parent->getAsNames();
        }
        if (null !== $this->parentPropertyName) {
            $path[] = $this->parentPropertyName;
        }

        if ([] === $path) {
            throw PathException::emptyPathAccess(static::class);
        }

        return $path;
    }

    /**
     * @return ArrayIterator<int, non-empty-string>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getAsNames());
    }

    /**
     * Provides all property-read tags from the docblock of the class this method was invoked on and its parent classes/interfaces.
     *
     * @param non-empty-list<non-empty-string> $targetTags
     *
     * @return array<string, class-string<PropertyAutoPathInterface>>
     * @throws ParseException
     */
    protected function getAutoPathProperties(array $targetTags = ['property-read']): array
    {
        if (null === $this->properties) {
            $this->properties = $this->getDocblockTraitEvaluator($targetTags)->parseProperties(
                static::class,
                true
            );
        }

        return array_filter(
            $this->properties,
            static fn (string $propertyClass): bool => is_subclass_of($propertyClass, PropertyAutoPathInterface::class)
        );
    }

    /**
     * @param list<array{0: PropertyPathInterface, 1: PropertyPathInterface}> $paths The first index
     *                                        in each item is the {@link PropertyPathInterface} of this
     *                                        {@link TypeInterface} instance from which we want to
     *                                        redirect to another property or attribute (it is thus
     *                                        expected to have only one segment). The second index
     *                                        is a {@link PropertyPathInterface} to which we want
     *                                        to redirect.
     *
     * @return array<non-empty-string, non-empty-list<non-empty-string>>
     *
     * @throws PathException
     * @throws ArrayException
     *
     * @see AliasableTypeInterface::getAliases()
     */
    protected function toAliases(array $paths): array
    {
        $keys = array_column($paths, 0);
        $keys = array_map([$this, 'getSourcePath'], $keys);
        $values = array_column($paths, 1);
        $values = array_map([$this, 'getTargetPath'], $values);

        return array_combine($keys, $values);
    }

    /**
     * Creates an instance via reflection. The constructor of the
     * given class will be circumvented, which results in an instance
     * that can be used for further path building but may not be
     * suited for other purposes.
     *
     * @template TImpl of \EDT\PathBuilding\PropertyAutoPathInterface
     *
     * @param class-string<TImpl> $className
     * @param non-empty-string|null               $parentPropertyName
     *
     * @return TImpl
     *
     * @throws Exception
     */
    protected static function createChild(string $className, ?PropertyAutoPathInterface $parent, ?string $parentPropertyName, array $constructorArgs = []): PropertyPathInterface
    {
        $class = new ReflectionClass($className);
        if ([] === $constructorArgs) {
            $constructor = $class->getConstructor();
            if (null === $constructor || 0 === $constructor->getNumberOfRequiredParameters()) {
                $childPathSegment = $class->newInstance();
            } else {
                $childPathSegment = $class->newInstanceWithoutConstructor();
            }
        } else {
            $childPathSegment = $class->newInstanceArgs($constructorArgs);
        }

        if (null !== $parent) {
            $childPathSegment->setParent($parent);
        }
        if (null !== $parentPropertyName) {
            $childPathSegment->setParentPropertyName($parentPropertyName);
        }

        return $childPathSegment;
    }

    /**
     * @param non-empty-list<non-empty-string> $targetTags
     * @internal
     */
    protected function getDocblockTraitEvaluator(array $targetTags): DocblockPropertyByTraitEvaluator
    {
        if (null === $this->docblockTraitEvaluator) {
            $this->docblockTraitEvaluator = PropertyEvaluatorPool::getInstance()->getEvaluator(
                PropertyAutoPathTrait::class,
                $targetTags
            );
        }

        return $this->docblockTraitEvaluator;
    }

    /**
     * @return non-empty-string
     *
     * @throws PathException
     */
    private function getSourcePath(PropertyPathInterface $sourcePath): string
    {
        return $sourcePath->getAsNamesInDotNotation();
    }

    /**
     * @return non-empty-list<non-empty-string>
     *
     * @throws PathException
     */
    private function getTargetPath(PropertyPathInterface $targetPath): array
    {
        return $targetPath->getAsNames();
    }

    /**
     * @return int<0, max>
     *
     * @see PropertyPathInterface::getCount()
     */
    public function getCount(): int
    {
        $pathCount = 0;
        if (null !== $this->parent) {
            $pathCount += $this->parent->getCount();
        }
        if (null !== $this->parentPropertyName) {
            $pathCount += 1;
        }

        return $pathCount;
    }
}
