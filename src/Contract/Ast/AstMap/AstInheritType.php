<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Contract\Ast\AstMap;

enum AstInheritType: string
{
    case EXTENDS = 'Extends';
    case IMPLEMENTS = 'Implements';
    case USES = 'Uses';
}
