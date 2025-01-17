<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;

interface AstMapExtractorInterface
{
    /**
     * @throws AstException
     */
    public function extract(): AstMapInterface;
}
