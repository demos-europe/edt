<?php

declare(strict_types=1);

namespace Tests\Utilities;

use Tests\Utilities\subnamespace\SingleTemplateClassForProperty as SingleSubNS;
use Tests\Utilities\subnamespace\DoubleTemplateClassForProperty as DoubleSubNS;

/**
 * @template T of object
 *
 * first level:
 * @property-read SingleTemplateClassForProperty<string> $propertyA
 * @property-read DoubleTemplateClassForProperty<string, string> $propertyB
 * @property-read DoubleSubNS<string, string> $propertyB2
 *
 * second level:
 * @property-read SingleTemplateClassForProperty<SingleSubNS<string>> $propertyC
 * @property-read DoubleTemplateClassForProperty<string, DoubleSubNS<string, string>> $propertyD
 *
 * second level with template parameter:
 * @property-read SingleTemplateClassForProperty<SingleSubNS<T>> $propertyE
 * @property-read DoubleTemplateClassForProperty<T, DoubleSubNS<T, T>> $propertyF
 */
class ClassWithProperties
{

}
