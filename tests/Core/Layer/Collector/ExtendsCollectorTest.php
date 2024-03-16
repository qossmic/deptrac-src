<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReferenceBuilder;
use Qossmic\Deptrac\Core\Ast\AstMapExtractor;
use Qossmic\Deptrac\Core\Layer\Collector\ExtendsCollector;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;

final class ExtendsCollectorTest extends TestCase
{
    public static function dataProviderSatisfy(): iterable
    {
        yield [['value' => 'App\FizTrait'], false];
        yield [['value' => 'App\Bar'], false];
        yield [['value' => 'App\Baz'], false];
        yield [['value' => 'App\Foo'], true];
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
            ->implements('App\Bar', 2);
        $fooFileReference = $fooFileReferenceBuilder->build();

        $barFileReferenceBuilder = FileReferenceBuilder::create('bar.php');
        $barFileReferenceBuilder
            ->newClassLike('App\Bar', [], [])
            ->implements('App\Baz', 2);
        $barFileReference = $barFileReferenceBuilder->build();

        $bazFileReferenceBuilder = FileReferenceBuilder::create('baz.php');
        $bazFileReferenceBuilder->newClassLike('App\Baz', [], []);
        $bazFileReference = $bazFileReferenceBuilder->build();

        $fizTraitFileReferenceBuilder = FileReferenceBuilder::create('fiztrait.php');
        $fizTraitFileReferenceBuilder
            ->newClassLike('App\FizTrait', [], []);
        $fizTraitFileReference = $fizTraitFileReferenceBuilder->build();

        $fooBarFileReferenceBuilder = FileReferenceBuilder::create('foobar.php');
        $fooBarFileReferenceBuilder
            ->newClassLike('App\FooBar', [], [])
            ->extends('App\Foo', 2)
            ->trait('App\FizTrait', 4);
        $fooBarFileReference = $fooBarFileReferenceBuilder->build();

        $astMap = new AstMap([$fooFileReference, $barFileReference, $bazFileReference, $fooBarFileReference, $fizTraitFileReference]);
        $astMapExtractor = $this->createMock(AstMapExtractor::class);
        $astMapExtractor->method('extract')
                ->willReturn($astMap);

        $collector = new ExtendsCollector($astMapExtractor);
        $actual = $collector->satisfy(
            $configuration,
            $fooBarFileReference->classLikeReferences[0],
            $this->createMock(LayerResolverInterface::class),
        );

        self::assertSame($expected, $actual);
    }
}
