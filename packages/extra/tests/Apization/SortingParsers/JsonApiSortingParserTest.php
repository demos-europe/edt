<?php

declare(strict_types=1);

namespace Tests\Apization\SortingParsers;

use EDT\Apization\SortingParsers\JsonApiSortingParser;
use EDT\Querying\Contracts\SortMethodInterface;
use EDT\Querying\SortMethodFactories\PhpSortMethodFactory;
use EDT\Querying\SortMethods\Ascending;
use EDT\Querying\SortMethods\Descending;
use PHPUnit\Framework\TestCase;

class JsonApiSortingParserTest extends TestCase
{
    /**
     * @var JsonApiSortingParser
     */
    private $jsonApiSortingParser;

    protected function setUp(): void
    {
        parent::setUp();
        $sortMethodFactory = new PhpSortMethodFactory();
        $this->jsonApiSortingParser = new JsonApiSortingParser($sortMethodFactory);
    }

    public function testNull(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue(null);
        self::assertSame([], $result);
    }

    public function testSingleSortDefinitionAsc(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue('a.b.c');
        self::assertCount(1, $result);
        $singleResult = $result[0];
        self::assertInstanceOf(Ascending::class, $singleResult);
        $this->checkSortMethod($singleResult, 'a', 'b', 'c');
    }

    public function testSingleSortDefinitionDesc(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue('-a.b.c');
        self::assertCount(1, $result);
        $singleResult = $result[0];
        self::assertInstanceOf(Descending::class, $singleResult);
        $this->checkSortMethod($singleResult, 'a', 'b', 'c');
    }

    public function testSortDefinitionsAscDesc(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue('a.b.c,-b');
        self::assertCount(2, $result);
        $firstResult = $result[0];
        self::assertInstanceOf(Ascending::class, $firstResult);
        $this->checkSortMethod($firstResult, 'a', 'b', 'c');

        $secondResult = $result[1];
        self::assertInstanceOf(Descending::class, $secondResult);
        $this->checkSortMethod($secondResult, 'b');
    }

    public function testSortDefinitionsDescAsc(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue('-b.c,b');
        self::assertCount(2, $result);
        $firstResult = $result[0];
        self::assertInstanceOf(Descending::class, $firstResult);
        $this->checkSortMethod($firstResult, 'b', 'c');

        $secondResult = $result[1];
        self::assertInstanceOf(Ascending::class, $secondResult);
        $this->checkSortMethod($secondResult, 'b');
    }

    public function testSortDefinitionsDescDesc(): void
    {
        $result = $this->jsonApiSortingParser->createFromQueryParamValue('-b.c,-b');
        self::assertCount(2, $result);
        $firstResult = $result[0];
        self::assertInstanceOf(Descending::class, $firstResult);
        $this->checkSortMethod($firstResult, 'b', 'c');

        $secondResult = $result[1];
        self::assertInstanceOf(Descending::class, $secondResult);
        $this->checkSortMethod($secondResult, 'b');
    }

    private function checkSortMethod(SortMethodInterface $sortMethod, string ...$path): void
    {
        $propertyPaths = $sortMethod->getPropertyPaths();
        self::assertCount(1, $propertyPaths);
        $propertyPathA = $propertyPaths[0]->getPath();
        self::assertSame($path, iterator_to_array($propertyPathA));
        self::assertSame('', $propertyPathA->getSalt());
        self::assertSame(PHP_INT_MAX, $propertyPathA->getAccessDepth());
    }
}
