<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathTrait;
use Tests\data\Paths\AmbiguouslyNamedResource as NonNestedResource;
use Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource as AmbiguouslyNamedResource;
use Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource as NestedResource;
use Tests\data\Paths\nestedNamespace\NestedOnlyResource as NonNestedOnlyResource;
use Tests\data\Paths\NonNestedOnlyResource as NestedOnlyResource;

/**
 * @property-read \EDT\PathBuilding\End                           $id
 * @property-read End                                                        $title
 * @property-read BarResource                                                $bar
 * @property-read NonNestedResource                                          $aliasedNonNestedResource
 * @property-read NestedResource                                             $aliasedNestedResource
 * @property-read \Tests\data\Paths\nestedNamespace\AmbiguouslyNamedResource $fqsenNestedResource
 * @property-read \Tests\data\Paths\AmbiguouslyNamedResource                 $fqsenNonNestedResource
 * @property-read AmbiguouslyNamedResource                                   $nestedResource
 * @property-read NestedOnlyResource                                         $nonNestedOnlyResource
 * @property-read NonNestedOnlyResource                                      $nestedOnlyResource
 */
class DummyResource extends AbstractDummyResource
{
    use PropertyAutoPathTrait;
}
