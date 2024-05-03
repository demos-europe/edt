<?php

declare(strict_types=1);

namespace Tests\RequestHandling;

use EDT\JsonApi\RequestHandling\RequestConstraintFactory;
use EDT\JsonApi\RequestHandling\RequestWithBody;
use EDT\JsonApi\Requests\UpdateRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

class RequestTransformerTest extends TestCase
{
    private RequestWithBody $requestTransformer;
    private ReflectionMethod $splitRelationships;

    /**
     * @see RequestWithBody::splitRelationships()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->requestTransformer = new UpdateRequest(
            new class () implements EventDispatcherInterface {
                public function dispatch(object $event)
                {
                    throw new \Exception('Unexpected call');
                }
            },
            new Request(),
            Validation::createValidator(),
            new RequestConstraintFactory(1, false),
            512
        );
        $this->splitRelationships = new ReflectionMethod($this->requestTransformer, 'splitRelationships');
        $this->splitRelationships->setAccessible(true);
    }

    /**
     * @param array<non-empty-string, array{data: list<JsonApiRelationship>|JsonApiRelationship|null}> $relationships
     *
     * @dataProvider splitRelationshipsData
     */
    public function testGetCreationRequestBody(array $relationships, array $expectedToOne, array $expectedToMany): void
    {
        [$actualToOne, $actualToMany] = $this->splitRelationships->invoke($this->requestTransformer, $relationships);

        self::assertEquals($expectedToOne, $actualToOne);
        self::assertEquals($expectedToMany, $actualToMany);
    }

    public static function splitRelationshipsData(): array
    {
        $twoToOneAndOneEmptyToManyInput = [
            'bars' => [
                'data' => []
            ],
            'foobar' => [
                'data' => [
                    'id' => 'bbf4eb3c-ec00-4e06-b698-c27b62dd347d',
                    'type' => 'Foobar'
                ]
            ],
            'foo' => [
                'data' => [
                    'id' => 'f94a8cbf-932d-40d7-93f5-5b719ae821cc',
                    'type' => 'Foo'
                ]
            ]
        ];

        $twoToOneOutput = [
            'foobar' => [
                'id' => 'bbf4eb3c-ec00-4e06-b698-c27b62dd347d',
                'type' => 'Foobar',
            ],
            'foo' => [
                'id' => 'f94a8cbf-932d-40d7-93f5-5b719ae821cc',
                'type' => 'Foo',
            ],
        ];
        $oneEmptyToManyOutput = [
            'bars' => [],
        ];

        $twoToOneAndOneToManyInput = [
            'bars' => [
                "data" => [
                    [
                        'id' => 'f34a8cbf-932d-40d7-93f5-5b719ae821cc',
                        'type' => 'Bar',
                    ], [
                        'id' => 'f24a8cbf-932d-40d7-93f5-5b719ae821cc',
                        'type' => 'Bar',
                    ],
                ]
            ],
            'foobar' => [
                'data' => [
                    'id' => 'bbf4eb3c-ec00-4e06-b698-c27b62dd347d',
                    'type' => 'Foobar'
                ]
            ],
            'foo' => [
                'data' => [
                    'id' => 'f94a8cbf-932d-40d7-93f5-5b719ae821cc',
                    'type' => 'Foo'
                ]
            ]
        ];
        $oneToManyOutput = [
            'bars' => [
                [
                    'id' => 'f34a8cbf-932d-40d7-93f5-5b719ae821cc',
                    'type' => 'Bar',
                ], [
                    'id' => 'f24a8cbf-932d-40d7-93f5-5b719ae821cc',
                    'type' => 'Bar',
                ],
            ],
        ];

        $twoToOneInput = [
            'foobar' => [
                'data' => [
                    'id' => 'bbf4eb3c-ec00-4e06-b698-c27b62dd347d',
                    'type' => 'Foobar'
                ]
            ],
            'foo' => [
                'data' => [
                    'id' => 'f94a8cbf-932d-40d7-93f5-5b719ae821cc',
                    'type' => 'Foo'
                ]
            ]
        ];

        return [
            [$twoToOneAndOneEmptyToManyInput, $twoToOneOutput, $oneEmptyToManyOutput],
            [$twoToOneAndOneToManyInput, $twoToOneOutput, $oneToManyOutput],
            [$twoToOneInput, $twoToOneOutput, []],
        ];
    }
}
