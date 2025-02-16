<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\Parser;

use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Deptrac\Deptrac\Core\Ast\AstMap\DependencyToken;
use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\FunctionLikeExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\TypeResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Tests\Deptrac\Deptrac\Core\Ast\ArrayAssertionTrait;

final class FunctionLikeExtractorTest extends TestCase
{
    use ArrayAssertionTrait;

    public function testPropertyDependencyResolving(): void
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForNewestSupportedVersion(),
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            [
                new FunctionLikeExtractor($typeResolver),
            ]
        );

        $filePath = __DIR__.'/Fixtures/MethodSignatures.php';
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(3, $astClassReferences);
        [$classA, $classB, $classC] = $astClassReferences;

        self::assertArrayValuesEquals(
            [],
            $this->getDependenciesAsString($classA)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesA::12 (returntype)',
            ],
            $this->getDependenciesAsString($classB)
        );

        self::assertArrayValuesEquals(
            [
                'Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesB::21 (parameter)',
                // NOTE: We are not yet tracking the call from MethodSignatureC::test()
                // to MethodSignatureA::foo().
            ],
            $this->getDependenciesAsString($classC)
        );
    }

    /**
     * @return string[]
     */
    private function getDependenciesAsString(?ClassLikeReference $classReference): array
    {
        if (null === $classReference) {
            return [];
        }

        return array_map(
            static function (DependencyToken $dependency) {
                return "{$dependency->token->toString()}::{$dependency->context->fileOccurrence->line} ({$dependency->context->dependencyType->value})";
            },
            $classReference->dependencies
        );
    }
}
