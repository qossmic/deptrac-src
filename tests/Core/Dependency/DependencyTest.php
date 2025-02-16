<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Dependency;

use Deptrac\Deptrac\Contract\Ast\DependencyContext;
use Deptrac\Deptrac\Contract\Ast\DependencyType;
use Deptrac\Deptrac\Contract\Ast\FileOccurrence;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Deptrac\Deptrac\Core\Dependency\Dependency;
use PHPUnit\Framework\TestCase;

final class DependencyTest extends TestCase
{
    public function testGetSet(): void
    {
        $dependency = new Dependency(
            ClassLikeToken::fromFQCN('a'),
            ClassLikeToken::fromFQCN('b'), new DependencyContext(new FileOccurrence('/foo.php', 23), DependencyType::PARAMETER
            ));
        self::assertSame('a', $dependency->getDepender()->toString());
        self::assertSame('/foo.php', $dependency->getContext()->fileOccurrence->filepath);
        self::assertSame(23, $dependency->getContext()->fileOccurrence->line);
        self::assertSame('b', $dependency->getDependent()->toString());
    }
}
