<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use EDT\JsonApi\RequestHandling\ContentField;
use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\NamedTypeInterface;
use EDT\Wrapping\Contracts\Types\RelationshipFetchableTypeInterface;
use EDT\Wrapping\Contracts\Types\TransferableTypeInterface;
use EDT\Wrapping\Properties\AttributeSetabilityInterface;
use EDT\Wrapping\Properties\RelationshipInterface;
use EDT\Wrapping\Properties\ToManyRelationshipSetabilityInterface;
use EDT\Wrapping\Properties\ToOneRelationshipSetabilityInterface;
use Exception;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

trait PropertyUpdaterTrait
{
    /**
     * @template TEnt of object
     *
     * @param TEnt $entity
     * @param array<non-empty-string, AttributeSetabilityInterface<PathsBasedInterface, TEnt>> $updatabilities
     * @param array<non-empty-string, mixed> $requestAttributes
     *
     * @return list<bool> side effects corresponding to each updatability item
     */
    protected function updateAttributes(object $entity, array $updatabilities, array $requestAttributes): array
    {
        return array_map(
            static function (
                AttributeSetabilityInterface $updatability,
                string $propertyName
            ) use ($entity, $requestAttributes): bool {
                try {
                    return $updatability->updateAttributeValue($entity, $requestAttributes[$propertyName]);
                } catch (Exception $exception) {
                    throw new InvalidArgumentException("Update failed for attribute property '$propertyName'.", 0, $exception);
                }
            },
            $updatabilities,
            array_keys($updatabilities)
        );
    }

    /**
     * @template TEnt of object
     *
     * @param TEnt $entity
     * @param array<non-empty-string, ToOneRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEnt, object>> $updatabilities
     * @param array<non-empty-string, JsonApiRelationship|null> $requestRelationships
     *
     * @return list<bool> side effect indicators corresponding to each updatability item
     */
    protected function updateToOneRelationships(object $entity, array $updatabilities, array $requestRelationships): array
    {
        return array_map(
            function (
                ToOneRelationshipSetabilityInterface $updatability,
                string $propertyName
            ) use ($requestRelationships, $entity): bool {
                try {
                    $relationshipReference = $requestRelationships[$propertyName];
                    $relationshipValue = $this->determineToOneRelationshipValue(
                        $updatability->getRelationshipType(),
                        $updatability->getRelationshipConditions(),
                        $relationshipReference
                    );

                    return $updatability->updateToOneRelationship($entity, $relationshipValue);
                } catch (Exception $exception) {
                    throw new InvalidArgumentException("Update failed for to-one relationship property '$propertyName'", 0, $exception);
                }
            },
            $updatabilities,
            array_keys($updatabilities)
        );
    }

    /**
     * @template TEnt of object
     *
     * @param TEnt $entity
     * @param array<non-empty-string, ToManyRelationshipSetabilityInterface<PathsBasedInterface, PathsBasedInterface, TEnt, object>> $updatabilities
     * @param array<non-empty-string, list<JsonApiRelationship>> $requestRelationships
     *
     * @return list<bool> side effect indicators corresponding to each updatability item
     *
     * @throws Exception
     */
    protected function updateToManyRelationships(
        object $entity,
        array $updatabilities,
        array $requestRelationships
    ): array {
        return array_map(
            function (
                ToManyRelationshipSetabilityInterface $updatability,
                string $relationshipName
            ) use ($requestRelationships, $entity): bool {
                try {
                    $relationshipReferences = $requestRelationships[$relationshipName];
                    $relationshipValues = $this->determineToManyRelationshipValues(
                        $updatability->getRelationshipType(),
                        $updatability->getRelationshipConditions(),
                        $relationshipReferences
                    );

                    return $updatability->updateToManyRelationship($entity, $relationshipValues);
                } catch (Exception $exception) {
                    throw new InvalidArgumentException("Update failed for to-many relationship property '$relationshipName'.", 0, $exception);
                }
            },
            $updatabilities,
            array_keys($updatabilities)
        );
    }

    /**
     * @template TRel of object
     * @template TCond of PathsBasedInterface
     * @template TSort of PathsBasedInterface
     *
     * @param NamedTypeInterface&RelationshipFetchableTypeInterface<TCond, TSort, TRel> $relationshipType
     * @param list<TCond> $relationshipConditions
     * @param JsonApiRelationship|null $relationshipRef
     *
     * @return TRel|null
     *
     * @throws Exception
     */
    protected function determineToOneRelationshipValue(
        NamedTypeInterface&RelationshipFetchableTypeInterface $relationshipType,
        array $relationshipConditions,
        ?array $relationshipRef
    ): ?object {
        if (null === $relationshipRef) {
            // TODO: how to disallow a `null` relationship? can it be done with a condition?
            return null;
        }

        $expectedType = $relationshipType->getTypeName();
        Assert::same($relationshipRef[ContentField::TYPE], $expectedType);

        return $relationshipType->getEntityForRelationship($relationshipRef[ContentField::ID], $relationshipConditions);
    }

    /**
     * @template TRel of object
     * @template TCond of PathsBasedInterface
     * @template TSort of PathsBasedInterface
     *
     * @param NamedTypeInterface&RelationshipFetchableTypeInterface<TCond, TSort, TRel> $relationshipType
     * @param list<TCond> $relationshipConditions
     * @param list<JsonApiRelationship> $relationshipRefs
     *
     * @return list<TRel>
     */
    protected function determineToManyRelationshipValues(
        NamedTypeInterface&RelationshipFetchableTypeInterface $relationshipType,
        array $relationshipConditions,
        array $relationshipRefs
    ): array {
        if ([] === $relationshipRefs) {
            // TODO: how to disallow an empty relationship?
            return [];
        }

        Assert::allSame(array_column($relationshipRefs, ContentField::TYPE), $relationshipType->getTypeName());
        $relationshipIds = array_column($relationshipRefs, ContentField::ID);
        if ([] === $relationshipIds) {
            $relationshipValues = [];
        } else {
            $relationshipValues = $relationshipType->getEntitiesForRelationship($relationshipIds, $relationshipConditions, []);
            Assert::count($relationshipValues, count($relationshipRefs), 'Tried to fetch %d entities, only %d were available for update according to the given identifiers and applied conditions.');
        }

        return $relationshipValues;
    }

    /**
     * @param array<non-empty-string, RelationshipInterface<TransferableTypeInterface<PathsBasedInterface, PathsBasedInterface, object>>> $updatabilities
     *
     * @return array<non-empty-string, non-empty-string>
     */
    protected function mapToRelationshipIdentifiers(array $updatabilities): array
    {
        return array_map(
            static fn (RelationshipInterface $setability): string => $setability->getRelationshipType()->getTypeName(),
            $updatabilities
        );
    }
}
