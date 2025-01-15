<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast\AstMap;

use Qossmic\Deptrac\Contract\Ast\ReferenceExtractorInterface;

/**
 * Interface for defining deptrac dependencies inside "Reference Extractors".
 *
 * @see ReferenceExtractorInterface
 */
interface ReferenceBuilderInterface
{
    /**
     * @return list<string>
     */
    public function getTokenTemplates(): array;

    public function dependency(TokenInterface $token, int $occursAtLine, DependencyType $type): static;

    public function astInherits(TokenInterface $token, int $occursAtLine, AstInheritType $type): static;
}
