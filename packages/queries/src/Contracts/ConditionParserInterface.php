<?php

declare(strict_types=1);

namespace EDT\Querying\Contracts;

use EDT\Querying\ConditionParsers\Drupal\DrupalConditionFactoryInterface;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;

/**
 * When processing a Drupal filter {@link DrupalFilterParser} will handle potential grouping of conditions, but will
 * leave the actual conversion of a single Drupal condition into an instance of `TCondition` to implementations of this
 * class.
 *
 * TODO: evaluate if really needed, if not, remove and use {@link DrupalConditionFactoryInterface} directly
 * TODO: rename this class and child classes and their methods away from "Parser", as an actual parser works differently
 *
 * @phpstan-import-type DrupalFilterCondition from DrupalFilterParser
 *
 * @template TCondition of PathsBasedInterface
 */
interface ConditionParserInterface
{
    /**
     * @param DrupalFilterCondition $condition
     *
     * @return TCondition
     */
    public function parseCondition(array $condition): PathsBasedInterface;
}
