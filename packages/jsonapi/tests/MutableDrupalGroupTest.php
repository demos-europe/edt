<?php

declare(strict_types=1);

namespace Tests;

use EDT\ConditionFactory\MutableDrupalCondition;
use EDT\ConditionFactory\MutableDrupalGroup;
use EDT\Querying\ConditionFactories\PhpConditionFactory;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterParser;
use EDT\Querying\ConditionParsers\Drupal\DrupalFilterValidator;
use EDT\Querying\ConditionParsers\Drupal\PredefinedDrupalConditionFactory;
use EDT\Querying\Drupal\StandardOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class MutableDrupalGroupTest extends TestCase
{
    public function test()
    {
        $validator = $this->getValidator();

        $groupA = new MutableDrupalGroup(DrupalFilterParser::AND);
        $drupalFilter = $groupA->toDrupalArray('groupA');
        $validator->validateFilter($drupalFilter);

        $groupB = new MutableDrupalGroup(DrupalFilterParser::OR);
        $groupC = new MutableDrupalGroup( DrupalFilterParser::AND);
        $groupA->addChild($groupB, 'groupB');
        $groupA->addChild($groupC, 'groupC');

        $groupA->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupA->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupA->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupB->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupB->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupB->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupC->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupC->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');
        $groupC->addChild(MutableDrupalCondition::createWithValue('x', StandardOperator::EQUALS, true), 'anotherPrefix');

        $drupalFilter = $groupA->toDrupalArray('groupA');
        $validator->validateFilter($drupalFilter);

        self::assertTrue(true);
    }

    protected function getValidator(): DrupalFilterValidator
    {
        return new DrupalFilterValidator(Validation::createValidator(), new PredefinedDrupalConditionFactory(new PhpConditionFactory()));
    }
}
