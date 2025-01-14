<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\Parser;

use Deptrac\Deptrac\Contract\Ast\AstMap\DependencyType;
use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\KeywordExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\TypeResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ClassDocBlockExtractorTest extends TestCase
{
    private const EXPECTED = [
        ['Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassDocBlockDependencySister', DependencyType::PARAMETER],
        ['Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassDocBlockDependencyBrother', DependencyType::RETURN_TYPE],
        ['Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassDocBlockDependencyChild', DependencyType::VARIABLE],
        ['Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassDocBlockDependencySister', DependencyType::VARIABLE],
        ['Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassDocBlockDependencyBrother', DependencyType::VARIABLE],
    ];

    public function testMethodResolving(): void
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForNewestSupportedVersion(),
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            [
                new KeywordExtractor($typeResolver),
            ]
        );

        $filePath = __DIR__.'/Fixtures/ClassDocBlockDependency.php';
        $astFileReference = $parser->parseFile($filePath);

        $dependencies = $astFileReference->classLikeReferences[0]->dependencies;

        self::assertCount(5, $astFileReference->classLikeReferences[0]->dependencies);

        foreach ($dependencies as $key => $dependency) {
            self::assertSame(self::EXPECTED[$key][0], $dependency->token->toString());
            self::assertSame(self::EXPECTED[$key][1], $dependency->context->dependencyType);
        }
    }
}
