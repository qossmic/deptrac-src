<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\DefaultBehavior\Layer;

use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Deptrac\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Deptrac\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Deptrac\Deptrac\DefaultBehavior\Layer\Helpers\RegexCollector;

final class ClassNameRegexCollector extends RegexCollector
{
    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        return $reference->getToken()->match($this->getValidatedPattern($config));
    }

    protected function getPattern(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ClassNameRegexCollector needs the regex configuration.');
        }

        return $config['value'];
    }
}
