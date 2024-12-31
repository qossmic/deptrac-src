<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser;

use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\AnnotationReferenceExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\KeywordExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\TypeResolver;

final class AnnotationReferenceExtractorTest extends TestCase
{
    public function testPropertyDependencyResolving(): void
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForHostVersion(),
            new AstFileReferenceInMemoryCache(),
            new TypeResolver(),
            [
                new AnnotationReferenceExtractor($typeResolver),
                new KeywordExtractor($typeResolver),
            ]
        );

        $filePath = __DIR__.'/Fixtures/AnnotationDependency.php';
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;
        $annotationDependency = $astClassReferences[0]->dependencies;

        self::assertCount(2, $astClassReferences);
        self::assertCount(6, $annotationDependency);
        self::assertCount(0, $astClassReferences[1]->dependencies);

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\AnnotationDependencyChild',
            $annotationDependency[0]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[0]->context->fileOccurrence->filepath);
        self::assertSame(9, $annotationDependency[0]->context->fileOccurrence->line);
        self::assertSame('variable', $annotationDependency[0]->context->dependencyType->value);

        self::assertSame(
            'Symfony\Component\Finder\SplFileInfo',
            $annotationDependency[1]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[1]->context->fileOccurrence->filepath);
        self::assertSame(14, $annotationDependency[1]->context->fileOccurrence->line);
        self::assertSame('parameter', $annotationDependency[1]->context->dependencyType->value);

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\AnnotationDependencyChild',
            $annotationDependency[2]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[2]->context->fileOccurrence->filepath);
        self::assertSame(14, $annotationDependency[2]->context->fileOccurrence->line);
        self::assertSame('returntype', $annotationDependency[2]->context->dependencyType->value);

        self::assertSame(
            'Symfony\Component\Console\Exception\RuntimeException',
            $annotationDependency[3]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[3]->context->fileOccurrence->filepath);
        self::assertSame(14, $annotationDependency[3]->context->fileOccurrence->line);
        self::assertSame('throw', $annotationDependency[3]->context->dependencyType->value);

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\AnnotationDependencyChild',
            $annotationDependency[4]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[4]->context->fileOccurrence->filepath);
        self::assertSame(42, $annotationDependency[4]->context->fileOccurrence->line);
        self::assertSame('new', $annotationDependency[4]->context->dependencyType->value);

        self::assertSame(
            'Tests\Qossmic\Deptrac\Core\Ast\Parser\Fixtures\AnnotationDependencyChild',
            $annotationDependency[5]->token->toString()
        );
        self::assertSame($filePath, $annotationDependency[5]->context->fileOccurrence->filepath);
        self::assertSame(35, $annotationDependency[5]->context->fileOccurrence->line);
        self::assertSame('returntype', $annotationDependency[5]->context->dependencyType->value);
    }
}
