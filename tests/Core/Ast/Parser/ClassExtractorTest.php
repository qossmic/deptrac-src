<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser;

use Closure;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstMap\DependencyType;
use Qossmic\Deptrac\Contract\Ast\ParserInterface;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\NikicTypeResolver;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\CatchExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Extractors\PropertyExtractor;
use Qossmic\Deptrac\DefaultBehavior\Ast\Parser\NikicPhpParser;

final class ClassExtractorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testPropertyDependencyResolving(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/ClassExtract.php';
        $parser = $parserBuilder($filePath);
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(3, $astClassReferences[1]->dependencies);

        $dependencies = $astClassReferences[1]->dependencies;
        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\ClassAttribute',
            $dependencies[0]->token->toString()
        );
        self::assertSame(DependencyType::ATTRIBUTE, $dependencies[0]->context->dependencyType);
        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\ClassB',
            $dependencies[1]->token->toString()
        );
        self::assertSame(DependencyType::VARIABLE, $dependencies[1]->context->dependencyType);
        self::assertSame(
            'Throwable',
            $dependencies[2]->token->toString()
        );
        self::assertSame(DependencyType::CATCH, $dependencies[2]->context->dependencyType);
    }

    /**
     * @return list<array{ParserInterface}>
     */
    public static function createParser(): array
    {
        return [
            'Nikic Parser' => [self::createNikicParser(...)],
        ];
    }

    public static function createNikicParser(string $filePath): NikicPhpParser
    {
        $typeResolver = new NikicTypeResolver();
        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new PropertyExtractor($typeResolver),
            new CatchExtractor($typeResolver),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }
}
