<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Use_>
 */
final class UseExtractor implements ReferenceExtractorInterface
{
    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        if (Use_::TYPE_NORMAL === $node->type) {
            foreach ($node->uses as $use) {
                $referenceBuilder->dependency(ClassLikeToken::fromFQCN($use->name->toString()), $use->name->getLine(), DependencyType::USE);
            }
        }
    }

    public function getNodeType(): string
    {
        return Use_::class;
    }
}
