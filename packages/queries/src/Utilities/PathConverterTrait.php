<?php

declare(strict_types=1);

namespace EDT\Querying\Utilities;

use EDT\Querying\Contracts\PropertyPathInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use function is_array;
use function is_string;

trait PathConverterTrait
{
    /**
     * @return non-empty-list<non-empty-string>
     *
     * @throws InvalidArgumentException
     */
    protected static function inputPathToArray(mixed $inputPath): array
    {
        if (is_string($inputPath)) {
            $path = explode('.', $inputPath);
            Assert::allStringNotEmpty($path, "Invalid path `$inputPath`, contains empty segments.");

            return $path;
        }

        if (is_array($inputPath)) {
            Assert::isNonEmptyList($inputPath);
            Assert::allStringNotEmpty($inputPath);

            return $inputPath;
        }

        if ($inputPath instanceof PropertyPathInterface) {
            return $inputPath->getAsNames();
        }

        throw new InvalidArgumentException('Invalid input path provided');
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $inputPath
     *
     * @return non-empty-list<non-empty-string>
     *
     * @throws InvalidArgumentException
     */
    protected static function pathToArray(string|array|PropertyPathInterface $inputPath): array
    {
        if (is_string($inputPath)) {
            $path = explode('.', $inputPath);
            Assert::allStringNotEmpty($path, "Invalid path `$inputPath`, contains empty segments.");

            return $path;
        }

        if (is_array($inputPath)) {
            return $inputPath;
        }

        return $inputPath->getAsNames();
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string>|PropertyPathInterface $path
     *
     * @return non-empty-string
     */
    protected static function pathToString(string|array|PropertyPathInterface $path): string
    {
        if (is_string($path)) {
            return $path;
        }

        if (is_array($path)) {
            return implode('.', $path);
        }

        return $path->getAsNamesInDotNotation();
    }
}
