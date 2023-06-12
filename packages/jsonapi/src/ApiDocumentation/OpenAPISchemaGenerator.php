<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Components;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Tag;
use EDT\JsonApi\ResourceTypes\ResourceTypeInterface;
use EDT\Wrapping\Contracts\TypeProviderInterface;
use EDT\Wrapping\Properties\AttributeReadabilityInterface;
use EDT\Wrapping\Properties\CommonMark;
use EDT\Wrapping\Properties\PropertyReadabilityInterface;
use EDT\Wrapping\Properties\RelationshipReadabilityInterface;
use Exception;
use ReflectionException;
use Safe\Exceptions\StringsException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use function count;

final class OpenAPISchemaGenerator
{
    public function __construct(
        protected readonly TypeProviderInterface $typeProvider,
        protected readonly RouterInterface $router,
        protected readonly SchemaStore $schemaStore,
        protected readonly TranslatorInterface $translator,
        protected readonly int $defaultPageSize
    ) {}

    /**
     * @throws TypeErrorException
     */
    public function getOpenAPISpecification(): OpenApi
    {
        $openApi = new OpenApi(
            [
                'openapi' => '3.0.2',
                'info'    => [
                    'title'       => $this->trans('title'),
                    'description' => $this->trans('description'),
                    'version'     => '2.0',
                ],
                'paths'   => [],
                'tags'    => [],
            ]
        );

        $tags = array_map(
            fn (string $identifier): ?ResourceTypeInterface => $this->typeProvider->requestType($identifier)
                ->instanceOf(ResourceTypeInterface::class)
                ->getInstanceOrNull(),
            $this->typeProvider->getTypeIdentifiers()
        );
        $tags = array_filter(
            $tags,
            static fn (?ResourceTypeInterface $type): bool => null !== $type
        );

        $tags = array_map(function (ResourceTypeInterface $type): ResourceTypeInterface {
            // create schema information for all resource types

            $typeIdentifier = $type->getIdentifier();
            if (!$this->schemaStore->has($typeIdentifier)) {
                $schema = $this->createSchema($type);
                $this->schemaStore->set($typeIdentifier, $schema);
            }

            return $type;
        }, $tags);
        // remove non-directly accessible ones
        $tags = array_filter($tags, static fn (ResourceTypeInterface $type) => $type->isExposedAsPrimaryResource());
        $tags = array_map(function (ResourceTypeInterface $type) use ($openApi): Tag {
            // add routing information for directly accessible resource types
            $tag = $this->createTag($type);

            $listMethodPathItem = $this->createListMethodsPathItem($tag, $type);

            $entityMethodsPathItem = $this->createEntityMethodsPathItem($tag, $type);

            $baseUrl = $this->router->generate(
                'api_resource_list',
                ['resourceType' => $type->getIdentifier()]
            );

            $openApi->paths[$baseUrl] = $listMethodPathItem;

            $openApi->paths[$baseUrl.'/{resourceId}/'] = $entityMethodsPathItem;

            return $tag;
        }, $tags);

        $openApi->components = new Components(['schemas' => $this->schemaStore->all()]);
        $openApi->tags = array_values($tags);

        return $openApi;
    }

    /**
     * @throws TypeErrorException
     */
    private function createTag(ResourceTypeInterface $type): Tag
    {
        return new Tag(
            [
                'name'         => $this->trans(
                    'resource.section',
                    ['type' => $type->getIdentifier()]
                ),
            ]
        );
    }

    /**
     * @throws TypeErrorException
     */
    private function addListOperation(
        Tag $tag,
        ResourceTypeInterface $resource,
        PathItem $pathItem
    ): void {
        $okResponse = new Response(
            [
                'content' => [
                    'application/vnd.api+json' => [
                        'schema' => $this->wrapAsJsonApiResponseSchema(
                            $resource,
                            [
                                'type'  => 'array',
                                'items' => [
                                    '$ref' => $this->schemaStore->getSchemaReference($resource->getIdentifier()),
                                ],
                            ],
                            [],
                            true
                        ),
                    ],
                ],
            ]
        );

        $pathItem->get = new Operation(
            [
                'description' => $this->trans(
                    'method.list.description',
                    ['type' => $resource->getIdentifier()]
                ),
                'parameters'  => array_merge(
                    $this->getDefaultQueryParameters(),
                    $this->getPaginationParameters(),
                    $this->getFilterParameter()
                ),
                'responses'   => [
                    SymfonyResponse::HTTP_OK => $okResponse,
                ],
                'tags'        => [$tag->name],
            ]
        );
    }

    /**
     * @throws TypeErrorException
     */
    private function addGetOperation(
        Tag $tag,
        ResourceTypeInterface $resource,
        PathItem $pathItem
    ): void {
        $okResponse = new Response(
            [
                'content' => [
                    'application/vnd.api+json' => [
                        'schema' => $this->wrapAsJsonApiResponseSchema(
                            $resource,
                            [
                                '$ref' => $this->schemaStore->getSchemaReference($resource->getIdentifier()),
                            ],
                            [],
                            false
                        ),
                    ],
                ],
            ]
        );

        $pathItem->get = new Operation(
            [
                'description' => $this->trans(
                    'method.get.description',
                    ['type' => $resource->getIdentifier()]
                ),
                'responses'   => [
                    SymfonyResponse::HTTP_OK => $okResponse,
                ],
                'tags'        => [$tag->name],
            ]
        );
    }

    /**
     * @throws TypeErrorException
     */
    private function createEntityMethodsPathItem(Tag $tag, ResourceTypeInterface $resource): PathItem
    {
        $entityMethodsPathItem = new PathItem(
            [
                'parameters' => array_merge(
                    $this->getDefaultQueryParameters(),
                    [
                        new Parameter(
                            [
                                'in'          => 'path',
                                'name'        => 'resourceId',
                                'description' => $this->trans('resource.id'),
                            ]
                        ),
                    ]
                ),
            ]
        );

        $this->addGetOperation($tag, $resource, $entityMethodsPathItem);

        return $entityMethodsPathItem;
    }

    /**
     * @throws TypeErrorException
     */
    private function createListMethodsPathItem(Tag $tag, ResourceTypeInterface $resource): PathItem
    {
        $listMethodPathItem = new PathItem([]);
        $this->addListOperation($tag, $resource, $listMethodPathItem);

        return $listMethodPathItem;
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     * @throws TypeErrorException
     * @throws StringsException
     */
    private function createSchema(ResourceTypeInterface $type): Schema
    {
        $schemaProperties = [];
        foreach (array_merge(...$type->getReadableProperties()) as $propertyName => $readability) {
            $schemaProperties[$propertyName] = $readability instanceof RelationshipReadabilityInterface
                ? [
                    '$ref' => $this->schemaStore->getSchemaReference(
                        $readability->getRelationshipType()->getIdentifier()
                    ),
                ]
                : $this->resolveAttributeType($type, $propertyName, $readability);
        }

        return new Schema(['type' => 'object', 'properties' => $schemaProperties]);
    }

    /**
     * @param array{type: non-empty-string, items: array<non-empty-string, non-empty-string>}|array<non-empty-string, non-empty-string> $dataObjects
     *
     * @throws TypeErrorException
     */
    private function wrapAsJsonApiResponseSchema(
        ResourceTypeInterface $resource,
        array $dataObjects,
        array $includedObjects,
        bool $isList
    ): Schema {
        $data = [
            'type'       => 'object',
            'properties' => [
                'type'       => ['type' => 'string', 'default' => $resource->getIdentifier()],
                'attributes' => $dataObjects,
            ],
        ];

        if ($isList) {
            $data = [
                'type'  => 'array',
                'items' => $data,
            ];
        }

        $selfLink = $this->router->generate(
            'api_resource_list',
            ['resourceType' => $resource->getIdentifier()]
        );

        if (!$isList) {
            $selfLink .= '{resourceId}/';
        }

        $jsonApiResponse = [
            'type'       => 'object',
            'properties' => [
                'jsonapi' => [
                    'type'       => 'object',
                    'properties' => [
                        'version' => ['type' => 'string', 'default' => '1.0'],
                    ],
                ],
                'data'    => $data,
                'meta'    => ['type' => 'object'],
                'links'   => [
                    'type'       => 'object',
                    'properties' => [
                        'self' => [
                            'type'    => 'string',
                            'default' => $selfLink,
                        ],
                    ],
                ],
            ],
        ];

        if (0 < count($includedObjects)) {
            $jsonApiResponse['properties']['included'] = [
                'type'  => 'array',
                'items' => $includedObjects,
            ];
        }

        return new Schema($jsonApiResponse);
    }

    /**
     * @param non-empty-string $propertyName
     * @param AttributeReadabilityInterface<object> $readability
     *
     * @return array<non-empty-string, mixed>
     */
    private function resolveAttributeType(
        ResourceTypeInterface $resource,
        string $propertyName,
        AttributeReadabilityInterface $readability
    ): array {
        try {
            return $readability->getPropertySchema();
        } catch (Exception $exception) {
            throw new OpenApiGenerateException("Could not determine attribute type of resource property {$resource->getIdentifier()}::$propertyName", 0, $exception);
        }
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    private function getDefaultQueryParameters(): array
    {
        $this->schemaStore->findOrCreate(
            'parameters:include',
            static fn (): Schema => new Schema(['type' => 'array'])
        );

        $this->schemaStore->findOrCreate(
            'parameters:exclude',
            static fn (): Schema => new Schema(['type' => 'array'])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'include',
                    'description' => $this->trans('parameter.query.include'),
                    'schema'      => [
                        '$ref' => $this->schemaStore->getSchemaReference('parameters:include'),
                    ],
                ]
            ),
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'exclude',
                    'description' => $this->trans('parameter.query.exclude'),
                    'schema'      => [
                        '$ref' => $this->schemaStore->getSchemaReference('parameters:exclude'),
                    ],
                ]
            ),
        ];
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    private function getPaginationParameters(): array
    {
        $this->schemaStore->findOrCreate(
            'parameter:pagination_number',
            static fn (): Schema => new Schema([
                'type'    => 'number',
                'format'  => 'int64',
                'default' => 1,
            ])
        );

        $this->schemaStore->findOrCreate(
            'parameter:pagination_size',
            fn (): Schema => new Schema([
                'type'    => 'number',
                'format'  => 'int64',
                'default' => $this->defaultPageSize,
            ])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'page[number]',
                    'description' => $this->trans('parameter.query.page_number'),
                    'schema' => [
                        '$ref' => $this->schemaStore->getSchemaReference('parameter:pagination_number'),
                    ],
                ]
            ),
            new Parameter([
                'in'          => 'query',
                'name'        => 'page[size]',
                'description' => $this->trans('parameter.query.page_size'),
                'schema' => [
                    '$ref' => $this->schemaStore->getSchemaReference('parameter:pagination_size'),
                ],
            ]),
        ];
    }

    /**
     * @return list<Parameter>
     *
     * @throws TypeErrorException
     */
    private function getFilterParameter(): array
    {
        $this->schemaStore->findOrCreate(
            'parameter:filter',
            static fn (): Schema => new Schema([
                'type' => 'array',
            ])
        );

        return [
            new Parameter(
                [
                    'in'          => 'query',
                    'name'        => 'filter',
                    'description' => $this->trans('parameter.query.filter'),
                    'schema' => [
                        '$ref' => $this->schemaStore->getSchemaReference('parameter:filter'),
                    ],
                ]
            ),
        ];
    }

    /**
     * @param string               $id         #TranslationKey
     * @param array<string, mixed> $parameters
     */
    private function trans(string $id, array $parameters = []): string
    {
        return trim($this->translator->trans($id, $parameters, 'openapi', 'en'));
    }
}
