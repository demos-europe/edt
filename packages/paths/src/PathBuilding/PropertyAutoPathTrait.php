<?php

declare(strict_types=1);

namespace EDT\PathBuilding;

use ArrayIterator;
use EDT\Parsing\Utilities\ParseException;
use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
use EDT\Parsing\Utilities\Types\TypeInterface;
use EDT\PathBuilding\SegmentFactories\ReflectionSegmentFactory;
use EDT\PathBuilding\SegmentFactories\SegmentFactoryInterface;
use EDT\Querying\Contracts\PathException;
use EDT\Querying\Contracts\PropertyPathInterface;
use Exception;
use function array_key_exists;

/**
 * Denotes a path usable in a condition that can be finalized to a string in dot-notation.
 *
 * Classes using this trait can define one or multiple &#x00040;`property-read` docblock tags with a
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
    /**
     * Set this property to use a different factory than {@link ReflectionSegmentFactory}.
     *
     * As an alternative, you can also override {@link self::getSegmentFactory()}.
     *
     * Changing it will have no effect on {@link self::startPath()}, which is hardcoded to use
     * {@link ReflectionSegmentFactory::createSegment()}.
     */
    protected ?SegmentFactoryInterface $segmentFactory = null;

    /**
     * @internal
     */
    private ?PropertyAutoPathInterface $parent = null;

    /**
     * @var non-empty-string|null
     *
     * @internal
     */
    private ?string $parentPropertyName = null;

    /**
     * `null` if no parsing happened yet.
     *
     * @var array<non-empty-string, class-string>|null
     *
     * @internal
     */
    private ?array $properties = null;

    /**
     * @var array<non-empty-string, PropertyPathInterface>
     *
     * @internal
     */
    private array $children = [];

    /**
     * Will be initialized when {@link PropertyAutoPathTrait::getDocblockTraitEvaluator()} is called.
     *
     * @internal
     */
    private ?DocblockPropertyByTraitEvaluator $docblockTraitEvaluator = null;

    /**
     * Warning: even if {@link self::$segmentFactory} allows to create fully initialized
     * path segments it still can't be used here. Instead, this method will always rely on
     * instantiation via reflection ({@link ReflectionSegmentFactory::createSegment()}), thus
     * potentially creating an instance not set-up correctly.
     *
     * @param mixed ...$constructorArgs
     *
     * @throws PathBuildException
     */
    public static function startPath(...$constructorArgs): static
    {
        $implementingClass = static::class;
        if (!is_subclass_of($implementingClass, PropertyAutoPathInterface::class)) {
            throw PathBuildException::missingInterface($implementingClass, PropertyAutoPathInterface::class);
        }

        try {
            return ReflectionSegmentFactory::createSegment($implementingClass, null, null, array_values($constructorArgs));
        } catch (Exception $exception) {
            throw PathBuildException::startPathFailed($implementingClass, $exception);
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
            throw PathBuildException::missingInterface($implementingClass, PropertyAutoPathInterface::class);
        }

        // if we already created the child we avoid creating it again, as for each created child
        // the properties of the target will be parsed, even if the target type was parsed before
        if (!array_key_exists($propertyName, $this->children)) {
            try {
                $properties = $this->getAutoPathProperties();
                if (!array_key_exists($propertyName, $properties)) {
                    throw PathBuildException::createFromName($propertyName, $implementingClass, $this->docblockTraitEvaluator->getTargetTags());
                }

                $returnType = $properties[$propertyName];
                $this->children[$propertyName] = $this->getSegmentFactory()
                    ->createNextSegment($returnType, $this, $propertyName);
            } catch (ParseException $exception) {
                throw PathBuildException::getPropertyFailed($implementingClass, $propertyName, $exception);
            } catch (Exception $exception) {
                throw PathBuildException::genericCreateChild($this::class, $propertyName, $exception);
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
     * Provides property-read tags from the docblock of the class this method was invoked on and its parent classes/interfaces.
     *
     * Will ignore tags whose type does not extend {@link PropertyAutoPathInterface}.
     *
     * @param non-empty-list<PropertyTag> $targetTags
     *
     * @return array<non-empty-string, class-string<PropertyAutoPathInterface>>
     * @throws ParseException
     */
    protected function getAutoPathProperties(array $targetTags = [PropertyTag::PROPERTY_READ]): array
    {
        if (null === $this->properties) {
            $properties = $this->getDocblockTraitEvaluator($targetTags)->parseProperties(
                static::class,
                true
            );

            $properties = array_filter(
                $properties,
                static fn (TypeInterface $type): bool => $type instanceof ClassOrInterfaceType
            );

            $this->properties = array_map(
                static fn (ClassOrInterfaceType $propertyType): string => $propertyType->getFullyQualifiedName(),
                $properties
            );
        }

        return array_filter(
            $this->properties,
            static fn (string $propertyClass): bool => is_subclass_of($propertyClass, PropertyAutoPathInterface::class)
        );
    }

    /**
     * Override this method or set {@link self::$segmentFactory} to use a different factory than
     * {@link ReflectionSegmentFactory}.
     *
     * This will have no effect on {@link self::startPath()}, which is hardcoded to use
     * {@link ReflectionSegmentFactory::createSegment()}.
     */
    protected function getSegmentFactory(): SegmentFactoryInterface
    {
        if (null === $this->segmentFactory) {
            $this->segmentFactory = new ReflectionSegmentFactory();
        }

        return $this->segmentFactory;
    }

    /**
     * @param non-empty-list<PropertyTag> $targetTags
     * @internal
     */
    protected function getDocblockTraitEvaluator(array $targetTags): DocblockPropertyByTraitEvaluator
    {
        if (null === $this->docblockTraitEvaluator) {
            $this->docblockTraitEvaluator = PropertyEvaluatorPool::getInstance()->getEvaluator(
                [PropertyAutoPathTrait::class],
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
    protected function getSourcePath(PropertyPathInterface $sourcePath): string
    {
        return $sourcePath->getAsNamesInDotNotation();
    }

    /**
     * @return non-empty-list<non-empty-string>
     *
     * @throws PathException
     */
    protected function getTargetPath(PropertyPathInterface $targetPath): array
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
