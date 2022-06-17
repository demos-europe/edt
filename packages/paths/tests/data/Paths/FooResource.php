<?php

declare(strict_types=1);

namespace Tests\data\Paths;

use EDT\PathBuilding\End;
use EDT\PathBuilding\PathBuildException;

/**
 * @property-read End   $id
 * @property-read \EDT\PathBuilding\End   $barTitle
 * @property-read BarResource $bar
 * @property-read FooResource $foo
 */
class FooResource extends BaseFooResource
{
    /**
     * @throws PathBuildException
     */
    public function getAliases(): array
    {
        return $this->toAliases(
            [$this->barTitle, $this->bar->title],
        );
    }
}
