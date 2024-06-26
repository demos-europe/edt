<?php

declare(strict_types=1);

namespace EDT\DqlQuerying\ClassGeneration;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EDT\JsonApi\PropertyConfig\Builder\AttributeConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToManyRelationshipConfigBuilderInterface;
use EDT\JsonApi\PropertyConfig\Builder\ToOneRelationshipConfigBuilderInterface;
use EDT\Parsing\Utilities\Types\ClassOrInterfaceType;
use EDT\Parsing\Utilities\Types\TypeInterface;
use EDT\PathBuilding\DocblockPropertyByTraitEvaluator;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;
use ReflectionProperty;
use function array_key_exists;
use Nette\PhpGenerator\PhpNamespace;

class ResourceConfigBuilderFromEntityGenerator
{
    use EntityBasedGeneratorTrait;

    /**
     * @var array<non-empty-string, TypeInterface>
     */
    protected readonly array $existingProperties;

    /**
     * @param null|callable(TypeInterface): TypeInterface $entityTypeCallback adjust the entity type to be used in the property type hint
     * @param null|callable(TypeInterface): TypeInterface $relationshipTypeCallback adjust the relationship type to be used in the property type hint
     */
    public function __construct(
        protected readonly ClassOrInterfaceType $parentClass,
        protected readonly DocblockPropertyByTraitEvaluator $traitEvaluator,
        protected readonly mixed $entityTypeCallback = null,
        protected readonly mixed $relationshipTypeCallback = null
    ) {
        $this->existingProperties = $this->traitEvaluator
            ->parseProperties($this->parentClass->getFullyQualifiedName(), true);
    }

    /**
     * Generate a config builder class from the given base class.
     *
     * For each generated property, a comment can be added via the `$generatePropertyComments` parameter.
     * The comment of the property will contain a reference to the entity property it was based on.
     * In rare cases you may want to disable the generation of those comments to keep the generated
     * config class as independent of the entity it is based on as possible.
     *
     * @param non-empty-string $targetName
     * @param non-empty-string $targetNamespace TODO: use {@link PhpNamespace} as type instead
     */
    public function generateConfigBuilderClass(
        ClassOrInterfaceType $entityType,
        string $targetName,
        string $targetNamespace,
        bool $generatePropertyComments
    ): PhpFile {
        $newFile = new PhpFile();
        $newFile->setStrictTypes();

        $reflectionClass = new ReflectionClass($entityType->getFullyQualifiedName());

        $namespace = $newFile->addNamespace($targetNamespace);
        array_map([$namespace, 'addUse'], $this->parentClass->getAllFullyQualifiedNames());

        $class = $namespace->addClass($targetName);

        $class->addComment('WARNING: THIS CLASS IS AUTOGENERATED.');
        $class->addComment("MANUAL CHANGES WILL BE LOST ON RE-GENERATION.\n");
        $class->addComment('To add additional properties, you may want to');
        $class->addComment("create an extending class and add them there.\n");

        $class->setExtends($this->parentClass->getFullyQualifiedName());
        $class->addComment("@template-extends {$this->parentClass->getFullString(true)}");
        $class->addComment('');

        // skip properties that are already defined in the parent class
        // TODO: this is intended for something like the `id` property, so that it is only defined once and not in every type, however it may be problematic if something should be overridden, because of a different type  hint
        $properties = array_filter(
            $reflectionClass->getProperties(),
            fn (ReflectionProperty $property): bool => !array_key_exists($property->getName(), $this->existingProperties)
        );

        $this->processProperties(
            array_values($properties),
            function (
                ReflectionProperty $property,
                Column|OneToMany|OneToOne|ManyToOne|ManyToMany $doctrinePropertySetting
            ) use ($class, $entityType, $namespace, $generatePropertyComments): void {
                $targetType = $this->mapToClass($entityType, $doctrinePropertySetting, $property);
                $propertyName = $property->getName();

                // add uses
                array_map([$namespace, 'addUse'], $targetType->getAllFullyQualifiedNames());

                // build reference
                $reference = $generatePropertyComments
                    ? ' ' . $this->buildReference($entityType, $propertyName)
                    : '';

                // add property-read tag
                $shortString = $targetType->getFullString(true);
                $class->addComment("@property-read $shortString \$$propertyName$reference");
            }
        );

        return $newFile;
    }

    protected function mapToClass(ClassOrInterfaceType $entityClass, Column|OneToMany|OneToOne|ManyToOne|ManyToMany $annotationOrAttribute, ReflectionProperty $property): ClassOrInterfaceType
    {
        $originalEntityClassFqcn = $entityClass->getFullyQualifiedName();
        if (null !== $this->entityTypeCallback) {
            $entityClass = ($this->entityTypeCallback)($entityClass);
        }

        if ($annotationOrAttribute instanceof Column) {
            $class = AttributeConfigBuilderInterface::class;

            return ClassOrInterfaceType::fromFqcn($class, [$entityClass]);
        }

        $class = $annotationOrAttribute instanceof ManyToMany || $annotationOrAttribute instanceof OneToMany
            ? ToManyRelationshipConfigBuilderInterface::class
            : ToOneRelationshipConfigBuilderInterface::class;

        $targetEntityClass = $this->getTargetEntityClass($originalEntityClassFqcn, $property->getName(), $annotationOrAttribute);

        // TODO (#147): detect template parameters?
        $targetEntityClass = ClassOrInterfaceType::fromFqcn($targetEntityClass);
        if (null !== $this->relationshipTypeCallback) {
            $targetEntityClass = ($this->relationshipTypeCallback)($targetEntityClass);
        }

        $templateParameters = [
            $entityClass,
            // TODO (#147): check docblock further to detect types with template parameters
            $targetEntityClass
        ];

        return ClassOrInterfaceType::fromFqcn($class, $templateParameters);
    }
}
