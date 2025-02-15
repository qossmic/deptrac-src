<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Layer\Collector;

use Deptrac\Deptrac\Core\Ast\AstMap\AstInherit;
use Deptrac\Deptrac\Core\Ast\AstMap\AstMap;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Deptrac\Deptrac\Core\Ast\AstMapExtractor;
use Deptrac\Deptrac\Core\Layer\Collector\InheritanceLevelCollector;
use PHPUnit\Framework\TestCase;

final class InheritanceLevelCollectorTest extends TestCase
{
    public static function dataTests(): array
    {
        return [
            [1, 1, true],
            [2, 1, true],
            [3, 2, true],
            [1, 2, false],
            [2, 3, false],
            [3, 4, false],
        ];
    }

    /**
     * @dataProvider dataTests
     */
    public function testSatisfy(int $pathLevel, int $levelConfig, bool $expected): void
    {
        $classInherit = $this->createMock(AstInherit::class);
        $classInherit->method('getPath')
            ->willReturn(array_fill(0, $pathLevel, 1))
        ;

        $astMap = $this->createMock(AstMap::class);
        $astMap->method('getClassInherits')
            ->with(ClassLikeToken::fromFQCN(AstInherit::class))
            ->willReturn([$classInherit])
        ;

        $astMapExtractor = $this->createMock(AstMapExtractor::class);
        $astMapExtractor->method('extract')
            ->willReturn($astMap)
        ;

        $collector = new InheritanceLevelCollector($astMapExtractor);
        $actual = $collector->satisfy(
            ['value' => $levelConfig],
            new ClassLikeReference(ClassLikeToken::fromFQCN(AstInherit::class)),
        );

        self::assertSame($expected, $actual);
    }
}
