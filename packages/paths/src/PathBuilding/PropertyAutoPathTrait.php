<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use ArrayIterator;
use EDT\Parsing\Utilities\ParseException;
use EDT\Querying\Contracts\PropertyPathInterface;
use EDT\Wrapping\Contracts\Types\TypeInterface;
use Exception;
use ReflectionClass;
use Traversable;
use function array_key_exists;
use function get_class;

/**
 * Denotes a path usable in a condition that can be finalized to a string in dot-notation.
 *
 * Using classes can define one or multiple <code>property-read</code> annotations with a
 * type using {@link PropertyAutoPathTrait}. These can be accessed from the outside like normal (public)
 * properties and will return an instance of their return type with the current instance set as
 * parent. This allows the user to build a path by starting with a type and descending into its
 * relationships. When the desired relationship is reached it can be converted into an array
 * {@link PropertyAutoPathTrait::getAsNames() array} or {@link PropertyAutoPathTrait::getAsNamesInDotNotation() string}.
 *
 * You can optionally implement {@link IteratorAggregate} and {@link PropertyPathInterface} (in this order)
 * in your using class without the need to add any methods.
 */
trait PropertyAutoPathTrait
{
    /**
     * @var PropertyAutoPathTrait|null
     */
    private $parent;
    /**
     * @var string|null
     */
    private $parentPropertyName;
    /**
     * Will be used instead of {@link PropertyAutoPathTrait::createChild()} when set to non-null.
     * @var callable(string, PropertyAutoPathTrait, string): PropertyAutoPathTrait
     */
    private $childCreateCallback;

    /**
     * Null if no parsing happened yet.
     *
     * @var array<string,class-string>|null
     */
    private $properties;

    /**
     * @var array<string, PropertyAutoPathTrait>
     */
    private $children = [];

    /**
     * Use {@link PropertyAutoPathTrait::getDocblockTraitEvaluator()} to initialize.
     *
     * @var DocblockPropertyByTraitEvaluator|null
     */
    private $docblockTraitEvaluator;

    /**
     * @param mixed ...$constructorArgs
     * @return static
     * @throws PathBuildException
     */
    public static function startPath(...$constructorArgs): self
    {
        return static::createChild(static::class, null, null, $constructorArgs);
    }

    /**
     * @return PropertyAutoPathTrait
     * @throws PathBuildException
     */
    public function __get(string $propertyName): object
    {
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
            }
        }

        return  $this->children[$propertyName];
    }

    /**
     * @param PropertyAutoPathTrait&object $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

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
     * @return array<int,object> All objects that are part of this path, including the starting object without corresponding
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
     * @see PropertyPathInterface::getAsNames()
     */
    public function getAsNames(): array
    {
        $path = [];
        if (null !== $this->parent) {
            $path = $this->parent->getAsNames();
            $path[] = $this->parentPropertyName;
        }

        return $path;
    }

    /**
     * @return Traversable<int,string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getAsNames());
    }

    /**
     * Provides all property-read tags from the docblock of the class this method was invoked on and its parent classes/interfaces.
     *
     * @return array<string,class-string>
     * @throws ParseException
     */
    protected function getAutoPathProperties(): array
    {
        if (null === $this->properties) {
            $this->properties = $this->getDocblockTraitEvaluator()->parseProperties(static::class, true);
        }

        return $this->properties;
    }

    /**
     * @param array<int, PropertyPathInterface> ...$paths The first index (`0`) is the {@link PropertyPathInterface}
     *                                   of this {@link TypeInterface} instance from which we want
     *                                   to redirect to another property or attribute (it is thus
     *                                   expected to have only one segment). The second index (`1`)
     *                                   is a {@link PropertyPathInterface} to which we want to
     *                                   redirect.
     * @return array<string,array<int,string>>
     * @throws PathBuildException
     *
     * @see TypeInterface::getAliases()
     */
    protected function toAliases(array ...$paths): array
    {
        $keys = array_column($paths, 0);
        $keys = array_map(static function (PropertyPathInterface $path): string {
            return $path->getAsNamesInDotNotation();
        }, $keys);
        $values = array_column($paths, 1);
        $values = array_map(static function (PropertyPathInterface $path): array {
            return $path->getAsNames();
        }, $values);

        return array_combine($keys, $values);
    }

    /**
     * Creates an instance via reflection. The constructor of the
     * given class will be circumvented, which results in an instance
     * that can be used for further path building but may not be
     * suited for other purposes.
     *
     * @param class-string<PropertyAutoPathTrait> $className
     * @param PropertyAutoPathTrait|null $parent
     *
     * @return PropertyAutoPathTrait
     *
     * @throws PathBuildException
     */
    private static function createChild(string $className, ?object $parent, ?string $parentPropertyName, array $constructorArgs = []): object
    {
        try {
            $class = new ReflectionClass($className);
            /** @var PropertyAutoPathTrait $childPathSegment */
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

            if ('' === $parentPropertyName) {
                throw PathBuildException::childWithEmptyParentPropertyName();
            }

            if (null !== $parent) {
                $childPathSegment->setParent($parent);
            }
            if (null !== $parentPropertyName) {
                $childPathSegment->setParentPropertyName($parentPropertyName);
            }

            return $childPathSegment;
        } catch (Exception $e) {
            throw PathBuildException::genericCreateChild(get_class($parent), $parentPropertyName, $e);
        }
    }

    protected function getDocblockTraitEvaluator(): DocblockPropertyByTraitEvaluator
    {
        if (null === $this->docblockTraitEvaluator) {
            $this->docblockTraitEvaluator = PropertyEvaluatorPool::getInstance()->getEvaluator(PropertyAutoPathTrait::class);
        }

        return $this->docblockTraitEvaluator;
    }
}
