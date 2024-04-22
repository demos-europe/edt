<?php

declare(strict_types=1);

namespace EDT\JsonApi\Validation;

use EDT\Querying\Contracts\PathsBasedInterface;
use EDT\Wrapping\Contracts\Types\PropertyReadableTypeInterface;
use EDT\Wrapping\Utilities\SchemaPathProcessor;
use Exception;
use Webmozart\Assert\Assert;

/**
 * Validate include definitions against a type.
 *
 * @see https://jsonapi.org/format/#fetching-includes
 */
class IncludeValidator
{
    public function __construct(
        protected readonly SchemaPathProcessor $schemaPathProcessor,
    ) {}

    /**
     * Assert each property in the given list of property paths is set as readable in the corresponding type,
     * starting with the given type instance for each of the first segments.
     *
     * The required include format can be ensured by calling {@link assertIncludeFormat} first.
     *
     * @param list<non-empty-list<non-empty-string>> $includes a list of paths
     * @param PropertyReadableTypeInterface<PathsBasedInterface, PathsBasedInterface, object> $type
     *
     * @throws Exception if any path segment in the given paths is not set as readable in the corresponding type; thrown at the first problem
     */
    public function assertIncludesAgainstType(array $includes, PropertyReadableTypeInterface $type): void
    {
        array_map(
            fn (array $includePath) => $this->schemaPathProcessor->verifyExternReadablePath($type, $includePath, false),
            $includes
        );
    }

    /**
     * Converts the raw include string to a more structured format.
     *
     * The method expects the given string to adhere to the
     * [format specified by the JSON:API specification](https://jsonapi.org/format/#fetching-includes).
     * I.e. a list of property paths, separated by comma characters, e.g. in the context of a book this may be
     * something like `'author.books,tags'`, to retrieve not only a specific book but also its tags and all books of the
     * same author.
     *
     * If the given string adheres to that format, the return will be a list of property paths, with each path being
     * a list of property names. E.g. `[['author','book'],'tags']` in the example given above.
     *
     * If the given string does not adhere to the specified format, an exception will be thrown.
     *
     * This method will **only** assert the correct format.
     * To verify the content of the include definition is valid, use {@link self::assertIncludesAgainstType()}.
     *
     * @param string $rawIncludes
     *
     * @return list<non-empty-list<non-empty-string>>
     */
    public function assertIncludeFormat(string $rawIncludes): array
    {
        return array_map(
            static fn(string $include): array => array_map(
                static function (string $segment): string {
                    Assert::stringNotEmpty($segment);
                    return $segment;
                },
                explode('.', $include)
            ),
            explode(',', $rawIncludes)
        );
    }
}
