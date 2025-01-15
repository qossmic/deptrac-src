<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser\Extractors;

use PhpParser\Node;
use Qossmic\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeResolverInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<\PhpParser\Node\Stmt\TraitUse>
 */
class TraitUseExtractor implements ReferenceExtractorInterface
{
    public function __construct(private readonly TypeResolverInterface $typeResolver) {}

    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, ...$node->traits) as $classLikeName) {
            $referenceBuilder->astInherits(ClassLikeToken::fromFQCN($classLikeName), $node->getLine(), AstInheritType::USES);
        }
    }

    public function getNodeType(): string
    {
        return Node\Stmt\TraitUse::class;
    }
}
