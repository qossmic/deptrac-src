<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Core\Ast\Parser\Extractors;

use Deptrac\Deptrac\Core\Ast\AstMap\ReferenceBuilder;
use Deptrac\Deptrac\Core\Ast\Parser\TypeScope;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;

class ClassConstantExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof ClassConstFetch || !$node->class instanceof Name || $node->class->isSpecialClassName()) {
            return;
        }

        $referenceBuilder->constFetch($node->class->toCodeString(), $node->class->getLine());
    }
}
