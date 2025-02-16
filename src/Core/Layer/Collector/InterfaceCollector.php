<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Core\Layer\Collector;

use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;

class InterfaceCollector extends AbstractTypeCollector
{
    protected function getType(): ClassLikeType
    {
        return ClassLikeType::TYPE_INTERFACE;
    }
}
