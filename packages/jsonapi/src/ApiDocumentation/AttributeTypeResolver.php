<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\GetableProperty;
use function array_key_exists;
use function collect;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use function get_class;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use function is_array;
use function is_string;
use function strlen;

/**
 * Map Doctrine or native types to OpenAPI types.
 */
class AttributeTypeResolver
{
    /**
     * @var array<class-string,ReflectionClass>
     */
    private $classReflectionCache = [];

    /**
     * @var array<class-string,array<string,GetableProperty>>
     */
    private $propertiesCache = [];

    /**
     * Return a valid `cebe\OpenApi` type declaration.
     *
     * @return array<string,string>
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPropertyType(
        AbstractResourceType $resourceType,
        string $propertyName
    ): array {
        $resourceClass = get_class($resourceType);
        if (!array_key_exists($resourceClass, $this->propertiesCache)) {
            $this->propertiesCache[$resourceClass] = collect(
                $resourceType->getValidatedProperties()
            )
                ->flatMap(static function (GetableProperty $property): array {
                    return [$property->getName() => $property];
                });
        }

        if ($this->propertiesCache[$resourceClass]->has($propertyName)) {
            $property = $this->propertiesCache[$resourceClass]->get($propertyName);

            if (null !== $property->getCustomReadCallback()) {
                return $this->resolveTypeFromCallable($property, $resourceClass, $propertyName);
            }
        }

        return $this->resolveTypeFromEntityClass($resourceType->getEntityClass(), $propertyName);
    }

    /**
     * @param class-string $entityClassName
     *
     * @return string[]
     *
     * @throws ReflectionException
     */
    private function resolveTypeFromEntityClass(
        string $entityClassName,
        string $propertyName
    ): array {
        if (!array_key_exists($entityClassName, $this->classReflectionCache)) {
            $this->classReflectionCache[$entityClassName] = new ReflectionClass($entityClassName);
        }

        if (!$this->classReflectionCache[$entityClassName]->hasProperty($propertyName)) {
            throw new UnexpectedValueException("API references non-existent property $propertyName on $entityClassName.");
        }

        $propertyReflection = $this->classReflectionCache[$entityClassName]->getProperty(
            $propertyName
        );

        $reader = new AnnotationReader();

        $column = $reader->getPropertyAnnotation($propertyReflection, Column::class);
        $id = $reader->getPropertyAnnotation($propertyReflection, Id::class);

        $docComment = $propertyReflection->getDocComment();

        if ($id instanceof Id) {
            return [
                'type'        => 'string',
                'format'      => 'uuid',
                'description' => $this->formatDescriptionFromDocblock($docComment),
            ];
        }

        if ($column instanceof Column) {
            $dqlTypeMapping = $this->mapDqlType($column->type);
            $dqlTypeMapping['description'] = $this->formatDescriptionFromDocblock($docComment);

            return $dqlTypeMapping;
        }

        return ['type' => 'unresolved'];
    }

    /**
     * Map a native type from a type reflection.
     */
    private function mapNativeType(ReflectionNamedType $reflectionType): string
    {
        $nativeType = $reflectionType->getName();

        switch ($nativeType) {
            case 'int':
                $type = 'number';
                break;

            case 'array':
                /*
                 * Arrays can be either arrays or hashmaps in PHP. This is currently not properly
                 * handled and all arrays are assumed to be just arrays.
                 *
                 * @improve T24976
                 */

            default:
                $type = $nativeType;
        }

        return $type;
    }

    private function mapDqlType(string $dqlType): array
    {
        $format = null;

        switch ($dqlType) {
            case 'string':
            case 'text':
                $type = 'string';
                break;

            case 'integer':
                $type = 'integer';
                $format = 'int32';
                break;

            case 'boolean':
                $type = 'boolean';
                break;

            case 'datetime':
                $type = 'string';
                $format = 'iso8601';
                break;

            default:
                $type = 'unknown: '.$dqlType;
        }

        $result = ['type' => $type];
        if (null !== $format) {
            $result['format'] = $format;
        }

        return $result;
    }

    private function resolveTypeFromCallable(
        GetableProperty $property,
        string $resourceClass,
        string $propertyName
    ): array {
        $customReadCallback = $property->getCustomReadCallback();

        try {
            if (is_array($customReadCallback)) {
                $callbackClassReflection = new ReflectionClass($customReadCallback[0]);
                $functionReflection = $callbackClassReflection->getMethod(
                    $customReadCallback[1]
                );
            } else {
                $functionReflection = new ReflectionFunction($customReadCallback);
            }
        } catch (Throwable $e) {
            // This catch purely exists to have a convenient breakpoint if an unhandled variant of callables appears
            throw $e;
        }

        if (!$functionReflection->hasReturnType()) {
            // OpenAPI and JSON do not support void/mixed types

            throw new RuntimeException("Custom read callback without declared return type detected: $resourceClass::$propertyName");
        }

        $returnType = $functionReflection->getReturnType();
        if (!$returnType->isBuiltin()) {
            // OpenAPI and JSON do not support compound types on attributes
            // see: https://spec.openapis.org/oas/v3.1.0.html#data-types

            throw new RuntimeException("Custom read callback does not return a builtin type: $resourceClass::$propertyName");
        }

        return ['type' => $this->mapNativeType($returnType)];
    }

    /**
     * Combine the summary and description of a docblock to a CommonMark string
     *
     * This combines the summary (first line) and description (following lines except
     * any annotations) from a docblock into a CommonMark string which can
     * be used to fuel schema descriptions.
     */
    private function formatDescriptionFromDocblock($docBlock): string
    {
        $parsed = DocBlockFactory::createInstance()->create($docBlock);

        $result = $parsed->getSummary();

        $description = $parsed->getDescription();
        if (is_string($description) && 0 < strlen($description)) {
            $result .= "\n\n$description";
        }

        return $result;
    }
}
