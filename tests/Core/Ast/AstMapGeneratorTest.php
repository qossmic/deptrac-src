<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast;

use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Deptrac\Deptrac\Contract\Ast\AstMap\DependencyToken;
use Deptrac\Deptrac\Core\Ast\AstLoader;
use Deptrac\Deptrac\Core\Ast\AstMap\AstMap;
use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\AnnotationReferenceExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\ClassConstantExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\KeywordExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\TypeResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassB;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassC;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitC;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitClass;
use Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitD;

final class AstMapGeneratorTest extends TestCase
{
    use ArrayAssertionTrait;

    private function getAstMap(string $fixture): AstMap
    {
        $typeResolver = new TypeResolver();
        $astRunner = new AstLoader(
            new NikicPhpParser(
                (new ParserFactory())->createForNewestSupportedVersion(),
                new AstFileReferenceInMemoryCache(),
                $typeResolver,
                [
                    new AnnotationReferenceExtractor($typeResolver),
                    new AnonymousClassExtractor(),
                    new ClassConstantExtractor(),
                    new KeywordExtractor($typeResolver),
                ]
            ),
            new EventDispatcher()
        );

        return $astRunner->createAstMap([$fixture]);
    }

    public function testBasicDependencyClass(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/BasicDependency/BasicDependencyClass.php');

        self::assertArrayValuesEquals(
            [
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassA::9 (Extends)',
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::9 (Implements)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyClassB::class)))
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceA::13 (Implements)',
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyClassInterfaceB::13 (Implements)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyClassC::class)))
        );
    }

    public function testBasicTraitsClass(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/BasicDependency/BasicDependencyTraits.php');

        self::assertArrayValuesEquals(
            [],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitA::class)))
        );

        self::assertArrayValuesEquals(
            [],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitB::class)))
        );

        self::assertArrayValuesEquals(
            ['Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB::7 (Uses)'],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitC::class)))
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA::10 (Uses)',
                'Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitB::11 (Uses)',
            ],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitD::class)))
        );

        self::assertArrayValuesEquals(
            ['Tests\Deptrac\Deptrac\Core\Ast\Fixtures\BasicDependency\BasicDependencyTraitA::15 (Uses)'],
            $this->getInheritsAsString($astMap->getClassReferenceForToken(ClassLikeToken::fromFQCN(BasicDependencyTraitClass::class)))
        );
    }

    public function testIssue319(): void
    {
        $astMap = $this->getAstMap(__DIR__.'/Fixtures/Issue319.php');

        self::assertSame(
            [
                'Foo\Exception',
                'Foo\RuntimeException',
                'LogicException',
            ],
            array_map(
                static function (DependencyToken $dependency) {
                    return $dependency->token->toString();
                },
                $astMap->getFileReferences()[__DIR__.'/Fixtures/Issue319.php']->dependencies
            )
        );
    }

    /**
     * @return string[]
     */
    private function getInheritsAsString(?ClassLikeReference $classReference): array
    {
        if (null === $classReference) {
            return [];
        }

        return array_map('strval', $classReference->inherits);
    }
}
