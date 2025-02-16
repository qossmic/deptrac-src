<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Core\Ast\AstMap\Variable;

use Deptrac\Deptrac\Contract\Ast\TokenInterface;
use Deptrac\Deptrac\Contract\Ast\TokenReferenceInterface;

/**
 * @psalm-immutable
 */
class VariableReference implements TokenReferenceInterface
{
    public function __construct(private readonly SuperGlobalToken $tokenName) {}

    public function getFilepath(): ?string
    {
        return null;
    }

    public function getToken(): TokenInterface
    {
        return $this->tokenName;
    }
}
