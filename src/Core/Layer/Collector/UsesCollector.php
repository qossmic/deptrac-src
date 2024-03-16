<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\Core\Ast\AstException;
use Qossmic\Deptrac\Core\Ast\AstMap\AstInheritType;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMapExtractor;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;

final class UsesCollector implements CollectorInterface
{
    private readonly AstMap $astMap;

    /**
     * @throws AstException
     */
    public function __construct(private AstMapExtractor $astMapExtractor)
    {
        $this->astMap = $this->astMapExtractor->extract();
    }

    public function satisfy(array $config, TokenReferenceInterface $reference, LayerResolverInterface $resolver): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $traitName = $this->getTraitName($config);

        foreach ($this->astMap->getClassInherits($reference->getToken()) as $inherit) {
            if (AstInheritType::USES === $inherit->type && $inherit->classLikeName->equals($traitName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool|string|array<string, string>> $config
     *
     * @throws InvalidCollectorDefinitionException
     */
    private function getTraitName(array $config): ClassLikeToken
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('UsesCollector needs the trait name as a string.');
        }

        return ClassLikeToken::fromFQCN($config['value']);
    }
}
