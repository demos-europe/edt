<?php

declare(strict_types=1);

namespace EDT\Parsing\Utilities;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class UseCollector extends NodeVisitorAbstract
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $useStatements = [];

    public function leaveNode(Node $node) {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $key = null === $use->alias ? $use->name->getLast() : $use->alias->toString();
                if ('' === $key) {
                    throw new \InvalidArgumentException('`use` statement key must not be empty.');
                }

                $useStatement = $use->name->toString();
                if ('' === $useStatement) {
                    throw new \InvalidArgumentException("`use` statement path for alias '$key' must not be empty.");
                }

                $this->useStatements[$key] = $useStatement;
            }
        }

        return null;
    }

    /**
     * Mapping from the implicit or explicit alias of the `use` statement to the corresponding path
     * which may lead to an actual type or just a namespace or trait.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }
}
