<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PropertyAutoPathTrait;
use Tests\data\Model\Book;

/**
 * @property-read \EDT\PathBuilding\End   $title
 * @property-read FooResource $foo
 *
 * The following property must not be made available for pathing, because its return type does not use the {@link PropertyAutoPathTrait}
 * @property-read Book $unavailableWithTrait
 *
 * The following properties must not be made available for pathing, because the trait was not configured to use something else than 'property-read'
 * @param End $paramAttribute
 * @param FooResource $paramRelationship
 * @property-write End $propertyWriteAttribute
 * @property-write FooResource $propertyWriteRelationship
 * @var End $varAttribute
 * @var FooResource $varRelationship
 * @property End $propertyAttribute
 * @property FooResource $propertyRelationship
 */
class BarResource
{
    use PropertyAutoPathTrait;

    private string $paramNeededForUnitTests = '';

    public function __construct(string $paramNeededForUnitTests)
    {
        $this->paramNeededForUnitTests = $paramNeededForUnitTests;
    }

    public function __toString(): string
    {
        return $this->getAsNamesInDotNotation().'|'.$this->paramNeededForUnitTests;
    }
}
