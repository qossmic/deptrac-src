<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser;

use Closure;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\ParserInterface;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanContainerDecorator;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanParser;

final class AnonymousClassExtractorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testPropertyDependencyResolving(Closure $parserBuilder): void
    {
        $filePath = __DIR__.'/Fixtures/AnonymousClass.php';
        $parser = $parserBuilder($filePath);
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(3, $astClassReferences);
        self::assertCount(0, $astClassReferences[0]->dependencies);
        self::assertCount(0, $astClassReferences[1]->dependencies);
        self::assertCount(2, $astClassReferences[2]->dependencies);

        $dependencies = $astClassReferences[2]->dependencies;

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\ClassA',
            $dependencies[0]->token->toString()
        );
        self::assertSame($filePath, $dependencies[0]->fileOccurrence->filepath);
        self::assertSame(19, $dependencies[0]->fileOccurrence->line);
        self::assertSame('anonymous_class_extends', $dependencies[0]->type->value);

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\InterfaceC',
            $dependencies[1]->token->toString()
        );
        self::assertSame($filePath, $dependencies[1]->fileOccurrence->filepath);
        self::assertSame(19, $dependencies[1]->fileOccurrence->line);
        self::assertSame('anonymous_class_implements', $dependencies[1]->type->value);
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
        $phpStanContainer = new PhpStanContainerDecorator(__DIR__, [$filePath]);

        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new AnonymousClassExtractor(),
        ];

        return new PhpStanParser($phpStanContainer, $cache, $extractors);
    }

    public static function createNikicParser(string $filePath): NikicPhpParser
    {
        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new AnonymousClassExtractor(),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }
}
