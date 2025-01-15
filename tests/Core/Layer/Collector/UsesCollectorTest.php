<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Layer\Collector;

use Deptrac\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Deptrac\Deptrac\Core\Ast\AstMap\AstMap;
use Deptrac\Deptrac\Core\Ast\AstMap\FileReferenceBuilder;
use Deptrac\Deptrac\Core\Ast\AstMapExtractor;
use Deptrac\Deptrac\Core\Layer\Collector\UsesCollector;
use PHPUnit\Framework\TestCase;

final class UsesCollectorTest extends TestCase
{
    public static function dataProviderSatisfy(): iterable
    {
        yield [['value' => 'App\FizTrait'], true];
        yield [['value' => 'App\Bar'], false];
        yield [['value' => 'App\Baz'], false];
        yield [['value' => 'App\Foo'], false];
        yield [['value' => 'App\None'], false];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $configuration, bool $expected): void
    {
        $fooFileReferenceBuilder = FileReferenceBuilder::create('foo.php');
        $fooFileReferenceBuilder
            ->newClassLike('App\Foo', [], [])
            ->astInherits(ClassLikeToken::fromFQCN('App\Bar'), 2, AstInheritType::IMPLEMENTS)
        ;
        $fooFileReference = $fooFileReferenceBuilder->build();

        $barFileReferenceBuilder = FileReferenceBuilder::create('bar.php');
        $barFileReferenceBuilder
            ->newClassLike('App\Bar', [], [])
            ->astInherits(ClassLikeToken::fromFQCN('App\Baz'), 2, AstInheritType::IMPLEMENTS)
        ;
        $barFileReference = $barFileReferenceBuilder->build();

        $bazFileReferenceBuilder = FileReferenceBuilder::create('baz.php');
        $bazFileReferenceBuilder->newClassLike('App\Baz', [], []);
        $bazFileReference = $bazFileReferenceBuilder->build();

        $fizTraitFileReferenceBuilder = FileReferenceBuilder::create('fiztrait.php');
        $fizTraitFileReferenceBuilder
            ->newClassLike('App\FizTrait', [], [])
        ;
        $fizTraitFileReference = $fizTraitFileReferenceBuilder->build();

        $fooBarFileReferenceBuilder = FileReferenceBuilder::create('foobar.php');
        $fooBarFileReferenceBuilder
            ->newClassLike('App\FooBar', [], [])
            ->astInherits(ClassLikeToken::fromFQCN('App\Foo'), 2, AstInheritType::EXTENDS)
            ->astInherits(ClassLikeToken::fromFQCN('App\FizTrait'), 4, AstInheritType::USES)
        ;
        $fooBarFileReference = $fooBarFileReferenceBuilder->build();

        $astMap = new AstMap(
            [$fooFileReference, $barFileReference, $bazFileReference, $fooBarFileReference, $fizTraitFileReference]
        );
        $astMapExtractor = $this->createMock(AstMapExtractor::class);
        $astMapExtractor->method('extract')
            ->willReturn($astMap)
        ;

        $collector = new UsesCollector($astMapExtractor);

        $stat = $collector->satisfy(
            $configuration,
            $fooBarFileReference->classLikeReferences[0]
        );

        self::assertSame($expected, $stat);
    }
}
