<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EDT\Parsing\Utilities\ClassOrInterfaceType;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Webmozart\Assert\Assert;

trait EntityBasedGeneratorTrait
{
    /**
     * @template TResult
     *
     * @param list<ReflectionProperty> $properties
     * @param callable(ReflectionProperty, Column|OneToMany|OneToOne|ManyToOne|ManyToMany): TResult $callback mapping from the property name to the result of the callback
     *
     * @return array<non-empty-string, TResult>
     */
    protected function processProperties(array $properties, callable $callback): array
    {
        $result = [];

        // Iterate over the properties of the entity class
        foreach ($properties as $property) {
            $annotations = $this->parseAnnotations($property);
            $attributes = $this->parseAttributes($property);

            // remove redundant annotations
            // TODO: O(n*m)
            $annotations = array_filter(
                $annotations,
                function (Column|OneToMany|OneToOne|ManyToOne|ManyToMany $annotation) use ($attributes): bool {
                    foreach ($attributes as $attribute) {
                        if ($annotation instanceof $attribute) {
                            return !$this->isEqual($annotation, $attribute);
                        }
                    }

                    return true;
                }
            );

            $totalCount = count($annotations) + count($attributes);
            Assert::lessThanEq($totalCount, 1, "Found $totalCount annotation and/or attribute on property `{$property->getName()}`, expected none or one.");
            if ([] !== $annotations) {
                $used = $annotations;
            } elseif ([] !== $attributes) {
                $used = $attributes;
            } else {
                continue;
            }

            $doctrineClass = array_pop($used);
            $result[$property->getName()] = $callback($property, $doctrineClass);
        }

        return $result;
    }

    protected function isEqual(
        Column|OneToMany|OneToOne|ManyToOne|ManyToMany $annotation,
        Column|OneToMany|OneToOne|ManyToOne|ManyToMany $attribute
    ): bool {
        if ($annotation instanceof OneToMany
            || $annotation instanceof OneToOne
            || $annotation instanceof ManyToMany) {
            if ($annotation->targetEntity !== $attribute->targetEntity
                || $annotation->cascade !== $attribute->cascade
                || $annotation->fetch !== $attribute->fetch
                || $annotation->orphanRemoval !== $attribute->orphanRemoval
                || $annotation->mappedBy !== $attribute->mappedBy) {
                return false;
            }
        }
        if ($annotation instanceof OneToMany
            || $annotation instanceof ManyToMany) {
            if ($annotation->indexBy !== $attribute->indexBy) {
                return false;
            }
        }
        if ($annotation instanceof ManyToMany
            || $annotation instanceof OneToOne
            || $annotation instanceof ManyToOne) {
            if ($annotation->inversedBy !== $attribute->inversedBy) {
                return false;
            }
        }
        if ($annotation instanceof ManyToOne) {
            if ($annotation->targetEntity !== $attribute->targetEntity
                || $annotation->cascade !== $attribute->cascade
                || $annotation->fetch !== $attribute->fetch) {
                return false;
            }
        }
        if ($annotation instanceof Column) {
            if ($annotation->insertable !== $attribute->insertable
                || $annotation->updatable !== $attribute->updatable
                || $annotation->type !== $attribute->type
                || $annotation->nullable !== $attribute->nullable
                || $annotation->generated !== $attribute->generated
                || $annotation->columnDefinition !== $attribute->columnDefinition
                || $annotation->name !== $attribute->name
                || $annotation->enumType !== $attribute->enumType
                || $annotation->length !== $attribute->length
                || $annotation->precision !== $attribute->precision
                || $annotation->scale !== $attribute->scale
                || $annotation->options !== $attribute->options
                || $annotation->unique !== $attribute->unique) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<Column|OneToMany|OneToOne|ManyToOne|ManyToMany>
     */
    protected function parseAnnotations(ReflectionProperty $property): array
    {
        $annotationReader = new AnnotationReader();
        $cacheProvider = new ArrayAdapter();
        $reader = new PsrCachedReader($annotationReader, $cacheProvider);

        return array_filter(
            array_values($reader->getPropertyAnnotations($property)),
            static fn (object $input): bool => $input instanceof Column
                || $input instanceof OneToMany
                || $input instanceof OneToOne
                || $input instanceof ManyToOne
                || $input instanceof ManyToMany
        );
    }

    /**
     * @return list<Column|OneToMany|OneToOne|ManyToOne|ManyToMany>
     */
    protected function parseAttributes(ReflectionProperty $property): array
    {
        $attributes = array_map(
            static fn (
                ReflectionAttribute $reflectionAttribute
            ): object => $reflectionAttribute->newInstance(),
            array_values($property->getAttributes())
        );

        return array_filter(
            $attributes,
            static fn (object $input): bool => $input instanceof Column
                || $input instanceof OneToMany
                || $input instanceof OneToOne
                || $input instanceof ManyToOne
                || $input instanceof ManyToMany
        );
    }

    /**
     * @param non-empty-string $propertyName
     *
     * @return non-empty-string
     */
    protected function buildReference(ClassOrInterfaceType $entityType, string $propertyName): string
    {
        $referencedClass = $entityType->getShortClassName();

        return "{@link $referencedClass::$propertyName}";
    }

    /**
     * @param non-empty-string $entityClass
     * @param non-empty-string $propertyName
     *
     * @return class-string
     */
    protected function getTargetEntityClass(string $entityClass, string $propertyName, OneToMany|ManyToOne|OneToOne|ManyToMany $annotationOrAttribute): string
    {
        $targetEntityClass = $annotationOrAttribute->targetEntity;
        Assert::notNull($targetEntityClass);
        if (!interface_exists($targetEntityClass) && !class_exists($targetEntityClass)) {
            throw new InvalidArgumentException(
                "Doctrine relationship was defined via annotation/attribute, but the type set as target entity (`$targetEntityClass`) could not be found. Make sure it uses the fully qualified name. The problematic relationship is: `$entityClass::$propertyName`."
            );
        }

        return $targetEntityClass;
    }
}
