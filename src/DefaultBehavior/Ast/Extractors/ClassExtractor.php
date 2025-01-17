<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Class_>
 */
final class ClassExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        if (null !== $node->name) {
            if ($node->extends instanceof Name) {
                $referenceBuilder->astInherits(ClassLikeToken::fromFQCN($node->extends->toCodeString()), $node->extends->getLine(), AstInheritType::EXTENDS);
            }

            foreach ($node->implements as $implement) {
                $referenceBuilder->astInherits(ClassLikeToken::fromFQCN($implement->toCodeString()), $implement->getLine(), AstInheritType::IMPLEMENTS);
            }
        }
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }
}
