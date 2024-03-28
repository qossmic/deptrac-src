<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser;

use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\DependencyToken;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ClassLikeExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\FunctionLikeExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\UseExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicTypeResolver;
use Qossmic\Deptrac\Core\Ast\Parser\ParserInterface;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanContainerDecorator;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanParser;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanTypeResolver;

final class FunctionLikeExtractorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testPropertyDependencyResolving(\Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/MethodSignatures.php';
        $parser = $parserBuilder($filePath);
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(3, $astClassReferences);
        [$classA, $classB, $classC] = $astClassReferences;

        self::assertEqualsCanonicalizing(
            [],
            $this->getDependenciesAsString($classA)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesA::12 (returntype)',
            ],
            $this->getDependenciesAsString($classB)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\MethodSignaturesB::21 (parameter)',
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
                return "{$dependency->token->toString()}::{$dependency->fileOccurrence->line} ({$dependency->type->value})";
            },
            $classReference->dependencies
        );
    }

    /**
     * @return list<array{ParserInterface}>
     */
    public static function createParser(): array
    {
        return [
            'Nikic Parser' => [self::createNikicParser(...)],
            'PHPStan Parser' => [self::createPhpStanParser(...)],
        ];
    }

    public static function createPhpStanParser(string $filePath): PhpStanParser
    {
        $typeResolver = new NikicTypeResolver();
        $phpStanContainer = new PhpStanContainerDecorator(__DIR__, [$filePath]);

        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new FunctionLikeExtractor($typeResolver, new PhpStanTypeResolver()),
        ];

        return new PhpStanParser($phpStanContainer, $cache, $extractors);
    }

    public static function createNikicParser(string $filePath): NikicPhpParser
    {
        $typeResolver = new NikicTypeResolver();

        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new FunctionLikeExtractor($typeResolver, new PhpStanTypeResolver()),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }
}
