<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures;

final class ClassA
{
}

final class ClassB
{
    public function foo()
    {
        return ClassA::class;
    }
}
