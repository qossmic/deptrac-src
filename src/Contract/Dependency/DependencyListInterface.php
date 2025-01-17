<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Dependency;

interface DependencyListInterface
{
    public function addDependency(DependencyInterface $dependency): void;
}
