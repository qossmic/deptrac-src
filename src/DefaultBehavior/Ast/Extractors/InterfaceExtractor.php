<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Interface_>
 */
final class InterfaceExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        foreach ($node->extends as $extend) {
            $referenceBuilder->astInherits(ClassLikeToken::fromFQCN($extend->toCodeString()), $extend->getLine(), AstInheritType::IMPLEMENTS);
        }
    }

    public function getNodeType(): string
    {
        return Interface_::class;
    }
}
