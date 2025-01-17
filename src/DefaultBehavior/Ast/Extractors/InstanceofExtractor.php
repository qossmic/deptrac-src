<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\DefaultBehavior\Ast\Extractors;

use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use Qossmic\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\AstMap\ReferenceBuilderInterface;
use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;
use Qossmic\Deptrac\Contract\Ast\TypeResolverInterface;
use Qossmic\Deptrac\Contract\Ast\TypeScope;

/**
 * @implements ReferenceExtractorInterface<Instanceof_>
 */
final class InstanceofExtractor implements ReferenceExtractorInterface
{
    public function __construct(private readonly TypeResolverInterface $typeResolver) {}

    public function processNode(Node $node, ReferenceBuilderInterface $referenceBuilder, TypeScope $typeScope): void
    {
        if ($node->class instanceof Name) {
            foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $node->class) as $classLikeName) {
                $referenceBuilder->dependency(ClassLikeToken::fromFQCN($classLikeName), $node->class->getLine(), DependencyType::INSTANCEOF);
            }
        }
    }

    public function getNodeType(): string
    {
        return Instanceof_::class;
    }
}
