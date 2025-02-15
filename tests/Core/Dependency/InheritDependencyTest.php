<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Dependency;

use Deptrac\Deptrac\Contract\Ast\DependencyContext;
use Deptrac\Deptrac\Contract\Ast\DependencyType;
use Deptrac\Deptrac\Contract\Ast\FileOccurrence;
use Deptrac\Deptrac\Core\Ast\AstMap\AstInherit;
use Deptrac\Deptrac\Core\Ast\AstMap\AstInheritType;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Deptrac\Deptrac\Core\Dependency\Dependency;
use Deptrac\Deptrac\Core\Dependency\InheritDependency;
use PHPUnit\Framework\TestCase;

final class InheritDependencyTest extends TestCase
{
    public function testGetSet(): void
    {
        $classLikeNameA = ClassLikeToken::fromFQCN('a');
        $classLikeNameB = ClassLikeToken::fromFQCN('b');
        $fileOccurrence = new FileOccurrence('a.php', 1);

        $dependency = new InheritDependency(
            $classLikeNameA,
            $classLikeNameB,
            $dep = new Dependency($classLikeNameA, $classLikeNameB, new DependencyContext(
                $fileOccurrence, DependencyType::PARAMETER)),
            $astInherit = new AstInherit($classLikeNameB, $fileOccurrence, AstInheritType::EXTENDS)
        );

        self::assertSame($classLikeNameA, $dependency->getDepender());
        self::assertSame($classLikeNameB, $dependency->getDependent());
        self::assertSame(1, $dependency->getContext()->fileOccurrence->line);
        self::assertSame($dep, $dependency->originalDependency);
        self::assertSame($astInherit, $dependency->inheritPath);
    }
}
