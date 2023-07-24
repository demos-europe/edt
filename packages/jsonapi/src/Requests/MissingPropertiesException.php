<?php

declare(strict_types=1);

namespace EDT\JsonApi\Requests;

use Exception;
use Throwable;

class MissingPropertiesException extends Exception
{
    /**
     * @param list<non-empty-string> $missingAttributes
     * @param list<non-empty-string> $missingToOneRelationships
     * @param list<non-empty-string> $missingToManyRelationships
     * @param non-empty-string $message
     * @param Throwable|null $previous
     */
    protected function __construct(
        protected readonly array $missingAttributes,
        protected readonly array $missingToOneRelationships,
        protected readonly array $missingToManyRelationships,
        string $message,
        ?Throwable $previous
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param array{0: list<non-empty-string>, 1: list<non-empty-string>, 2: list<non-empty-string>} $nonAvailableProperties
     */
    public static function nonAvailableProperties(array $nonAvailableProperties): self
    {
        $messages = array_map(
            static function (array $propertyNames, string $context): string {
                $namesString = implode(', ', $propertyNames);
                return "The following $context properties can not be accessed with this request: $namesString.";
            },
            $nonAvailableProperties,
            ['attributes', 'to-one relationships', 'to-many relationships']
        );

        return new self($nonAvailableProperties[0], $nonAvailableProperties[1], $nonAvailableProperties[2], implode(' ', $messages), null);
    }

    /**
     * @return list<non-empty-string>
     */
    public function getMissingAttributes(): array
    {
        return $this->missingAttributes;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getMissingToOneRelationships(): array
    {
        return $this->missingToOneRelationships;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getMissingToManyRelationships(): array
    {
        return $this->missingToManyRelationships;
    }
}
