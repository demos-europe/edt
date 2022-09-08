<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use Closure;
use EDT\JsonApi\ResourceTypes\AbstractResourceType;
use EDT\JsonApi\ResourceTypes\GetableProperty;
use EDT\Parsing\Utilities\DocblockTagParser;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionProperty;
use function array_key_exists;
use function collect;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use function get_class;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use function is_array;
use function is_callable;
use function strlen;

/**
 * Map Doctrine or native types to OpenAPI types.
 */
// TODO: determine if this class can be abstracted away from doctrine. If not, decide if it should remain in the jsonapi package
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
                ->mapWithKeys(static function (GetableProperty $property): array {
                    return [$property->getName() => $property];
                })
                ->all();
        }

        $resourceProperties = $this->propertiesCache[$resourceClass];
        if (array_key_exists($propertyName, $resourceProperties)) {
            $property = $resourceProperties[$propertyName];

            $customReadCallback = $property->getCustomReadCallback();
            if (null !== $customReadCallback) {
                return $this->resolveTypeFromCallable($customReadCallback, $resourceClass, $propertyName);
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

        if ($id instanceof Id) {
            return [
                'type'        => 'string',
                'format'      => 'uuid',
                'description' => $this->formatDescriptionFromDocblock($propertyReflection),
            ];
        }

        if ($column instanceof Column) {
            $dqlTypeMapping = $this->mapDqlType($column->type);
            $dqlTypeMapping['description'] = $this->formatDescriptionFromDocblock($propertyReflection);

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

    /**
     * @return array{type: string, format?: string}
     */
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

    /**
     * @param array{0: object, 1: string}|callable|Closure $customReadCallback
     *
     * @return array{type: string}
     *
     * @throws ReflectionException
     */
    private function resolveTypeFromCallable(
        $customReadCallback,
        string $resourceClass,
        string $propertyName
    ): array {
        try {
            $functionReflection = $this->reflectCustomReadCallback($customReadCallback);
        } catch (Throwable $e) {
            // This catch purely exists to have a convenient breakpoint if an unhandled variant of callables appears
            throw $e;
        }

        if (!$functionReflection->hasReturnType()) {
            // OpenAPI and JSON do not support void/mixed types

            throw new RuntimeException("Custom read callback without declared return type detected: $resourceClass::$propertyName");
        }

        $returnType = $functionReflection->getReturnType();
        if (!$returnType instanceof ReflectionNamedType || !$returnType->isBuiltin()) {
            // OpenAPI and JSON do not support compound types on attributes
            // see: https://spec.openapis.org/oas/v3.1.0.html#data-types

            throw new RuntimeException("Custom read callback does not return a builtin type: $resourceClass::$propertyName");
        }

        return ['type' => $this->mapNativeType($returnType)];
    }

    /**
     * @param array{0: object, 1: string}|callable|Closure $customReadCallback
     *
     * @return ReflectionMethod|ReflectionFunction
     *
     * @throws ReflectionException
     */
    private function reflectCustomReadCallback($customReadCallback): ReflectionFunctionAbstract
    {
        if (is_array($customReadCallback)) {
            return (new ReflectionClass($customReadCallback[0]))->getMethod(
                $customReadCallback[1]
            );
        }

        if (is_callable($customReadCallback)) {
            $customReadCallback = Closure::fromCallable($customReadCallback);
        }

        return new ReflectionFunction($customReadCallback);
    }

    /**
     * Combine the summary and description of a docblock to a CommonMark string
     *
     * This combines the summary (first line) and description (following lines except
     * any annotations) from a docblock into a CommonMark string which can
     * be used to fuel schema descriptions.
     */
    private function formatDescriptionFromDocblock(ReflectionProperty $reflectionProperty): string
    {
        $docblock = DocblockTagParser::createDocblock($reflectionProperty);
        if (null === $docblock) {
            return '';
        }

        $result = $docblock->getSummary();

        $description = (string) $docblock->getDescription();
        if (0 < strlen($description)) {
            $result .= "\n\n$description";
        }

        return $result;
    }
}
