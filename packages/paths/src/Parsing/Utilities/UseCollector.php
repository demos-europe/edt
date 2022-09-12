<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class UseCollector extends NodeVisitorAbstract
{
    /**
     * @var array<string, class-string>
     */
    private $useStatements = [];

    public function leaveNode(Node $node) {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $key = null === $use->alias ? $use->name->getLast() : $use->alias->toString();
                $this->useStatements[$key] = $use->name->toString();
            }
        }

        return null;
    }

    /**
     * @return array<string, class-string>
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }
}
