<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Parsing\Utilities\DocblockTagParser;
use EDT\Wrapping\Properties\AbstractReadability;
use EDT\Wrapping\Properties\AttributeReadability;
use EDT\Wrapping\Properties\ToManyRelationshipReadability;
use EDT\Wrapping\Properties\ToOneRelationshipReadability;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionProperty;
use function array_key_exists;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use function is_array;
use function is_callable;
use function is_string;
use function strlen;

/**
 * Map Doctrine or native types to OpenAPI types.
 *
 * TODO: abstract this class away from the doctrine parts and move doctrine parts into separate class in separate package (service or subclass)
 */
class AttributeTypeResolver
{
    /**
     * @var array<class-string, ReflectionClass>
     */
    private array $classReflectionCache = [];

    /**
     * Return a valid `cebe\OpenApi` type declaration.
     *
     * @param non-empty-string $propertyName
     * @param AttributeReadability|ToOneRelationshipReadability|ToManyRelationshipReadability $propertyReadability
     *
     * @return array{type: string, format?: non-empty-string, description?: string}
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getPropertyType(
        ResourceTypeInterface $resourceType,
        string $propertyName,
        AbstractReadability $propertyReadability
    ): array {
        $customReadCallback = $propertyReadability->getCustomReadCallback();
        if (null !== $customReadCallback) {
            return $this->resolveTypeFromCallable($customReadCallback, $resourceType::class, $propertyName);
        }

        return $this->resolveTypeFromEntityClass($resourceType->getEntityClass(), $propertyName);
    }

    /**
     * @param class-string $entityClassName
     *
     * @return array{type: non-empty-string, format?: non-empty-string, description?: string}
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
            $dqlTypeMapping = $this->mapDqlType($column);
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

        return match ($nativeType) {
            'int' => 'number',
            /*
             * Arrays can be either arrays or hashmaps in PHP. This is currently not properly
             * handled and all arrays are assumed to be just arrays.
             *
             * TODO @improve T24976
             */
            //'array' => $nativeType,
            default => $nativeType,
        };
    }

    /**
     * @return array{type: non-empty-string, format?: non-empty-string}
     */
    private function mapDqlType(Column $column): array
    {
        $format = null;
        $dqlType = $column->type;

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
                $type = 'unknown: '.(is_string($dqlType) ? $dqlType : 'non-string');
        }

        $result = ['type' => $type];
        if (null !== $format) {
            $result['format'] = $format;
        }

        return $result;
    }

    /**
     * @param callable(object): mixed $customReadCallback
     *
     * @return array{type: string}
     *
     * @throws ReflectionException
     */
    private function resolveTypeFromCallable(
        callable $customReadCallback,
        string $resourceClass,
        string $propertyName
    ): array {
        try {
            $functionReflection = $this->reflectCustomReadCallback($customReadCallback);
        } catch (Throwable $exception) {
            // This catch purely exists to have a convenient breakpoint if an unhandled variant of callables appears
            throw $exception;
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
     * @param callable(object): mixed $customReadCallback
     *
     * @return ReflectionMethod|ReflectionFunction
     *
     * @throws ReflectionException
     */
    private function reflectCustomReadCallback(callable $customReadCallback): ReflectionFunctionAbstract
    {
        if (is_array($customReadCallback)) {
            return (new ReflectionClass($customReadCallback[0]))->getMethod(
                $customReadCallback[1]
            );
        }

        if (is_string($customReadCallback)) {
            return new ReflectionFunction($customReadCallback);
        }

        return new ReflectionFunction($customReadCallback(...));
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
