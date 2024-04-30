<?php

declare(strict_types=1);

namespace EDT\JsonApi;

use EDT\JsonApi\ApiDocumentation\OpenApiDocumentBuilder;
use EDT\JsonApi\ApiDocumentation\SchemaStore;
use EDT\JsonApi\ApiDocumentation\TagStore;
use EDT\JsonApi\ResourceTypes\CreatableTypeInterface;
use EDT\JsonApi\ResourceTypes\DeletableTypeInterface;
use EDT\JsonApi\ResourceTypes\GetableTypeInterface;
use EDT\JsonApi\ResourceTypes\ListableTypeInterface;
use EDT\JsonApi\ResourceTypes\UpdatableTypeInterface;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use Webmozart\Assert\Assert;

/**
 * @template TCondition of PathsBasedInterface
 * @template TSorting of PathsBasedInterface
 */
class Manager
{
    /**
     * @var array<non-empty-string, GetableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>>
     */
    protected array $getableTypes = [];
    /**
     * @var array<non-empty-string, ListableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>>
     */
    protected array $listableTypes = [];
    /**
     * @var array<non-empty-string, UpdatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>>
     */
    protected array $updatableTypes = [];
    /**
     * @var array<non-empty-string, CreatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>>
     */
    protected array $creatableTypes = [];
    /**
     * @var array<non-empty-string, DeletableTypeInterface&TransferableTypeInterface<TCondition, TSorting, object>>
     */
    protected array $deletableTypes = [];
    /**
     * @var positive-int
     */
    protected int $paginationDefaultPageSize = 20;

    /**
     * @param positive-int $size
     */
    public function setPaginationDefaultPageSize(int $size): void
    {
        $this->paginationDefaultPageSize = $size;
    }

    /**
     * @param GetableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object> $type
     */
    public function addGetableType(GetableTypeInterface $type): void
    {
        $this->addToArray($type, $this->getableTypes);
    }

    /**
     * @param list<GetableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>> $types
     */
    public function addGetableTypes(array $types): void
    {
        array_map($this->addGetableType(...), $types);
    }

    /**
     * @param ListableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object> $type
     */
    public function addListableType(ListableTypeInterface $type): void
    {
        $this->addToArray($type, $this->listableTypes);
    }

    /**
     * @param UpdatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object> $type
     */
    public function addUpdatableType(UpdatableTypeInterface $type): void
    {
        $this->addToArray($type, $this->updatableTypes);
    }

    /**
     * @param CreatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object> $type
     */
    public function addCreatableType(CreatableTypeInterface $type): void
    {
        $this->addToArray($type, $this->creatableTypes);
    }

    /**
     * @param DeletableTypeInterface&TransferableTypeInterface<TCondition, TSorting, object> $type
     */
    public function addDeletableType(DeletableTypeInterface&NamedTypeInterface $type): void
    {
        $this->addToArray($type, $this->deletableTypes);
    }

    /**
     * @param list<ListableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>> $types
     */
    public function addListableTypes(array $types): void
    {
        array_map($this->addListableType(...), $types);
    }

    /**
     * @param list<UpdatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>> $types
     */
    public function addUpdatableTypes(array $types): void
    {
        array_map($this->addUpdatableType(...), $types);
    }

    /**
     * @param list<CreatableTypeInterface<TCondition, TSorting, object>&TransferableTypeInterface<TCondition, TSorting, object>> $types
     */
    public function addCreatableTypes(array $types): void
    {
        array_map($this->addCreatableType(...), $types);
    }

    /**
     * @param list<DeletableTypeInterface&TransferableTypeInterface<TCondition, TSorting, object>> $types
     */
    public function addDeletableTypes(array $types): void
    {
        array_map($this->addDeletableType(...), $types);
    }

    /**
     * Creates an instance to generate an {@link OpenAPI} schema from the types set in this instance so far.
     *
     * To activate the generation of documentation for specific actions, make sure to set the corresponding
     * configurations (e.g. {@link OpenApiDocumentBuilder::setGetActionConfig()} and
     * {@link OpenApiDocumentBuilder::setListActionConfig()}).
     */
    public function createOpenApiDocumentBuilder(): OpenApiDocumentBuilder
    {
        return new OpenApiDocumentBuilder(
            new SchemaStore(),
            new TagStore(),
            $this->paginationDefaultPageSize,
            $this->getableTypes,
            $this->listableTypes,
        );
    }

    /**
     * @template T of NamedTypeInterface
     *
     * @param T $type
     * @param array<non-empty-string, T> $array
     *
     * @return array<non-empty-string, T>
     */
    protected function addToArray(NamedTypeInterface $type, array &$array): array
    {
        $typeName = $type->getTypeName();
        Assert::keyNotExists($array, $typeName);

        return $array;
    }
}
