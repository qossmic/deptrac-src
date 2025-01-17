<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Dependency;

use Qossmic\Deptrac\Contract\Ast\AstMap\AstMapInterface;

interface DependencyEmitterInterface
{
    public function getName(): string;

    public function applyDependencies(AstMapInterface $astMap, DependencyListInterface $dependencyList): void;
}
