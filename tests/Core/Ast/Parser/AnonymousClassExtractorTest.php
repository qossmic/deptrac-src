<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\Parser;

use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\TypeResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class AnonymousClassExtractorTest extends TestCase
{
    public function testPropertyDependencyResolving(): void
    {
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForNewestSupportedVersion(),
            new AstFileReferenceInMemoryCache(),
            new TypeResolver(),
            [
                new AnonymousClassExtractor(),
            ]
        );

        $filePath = __DIR__.'/Fixtures/AnonymousClass.php';
        $astFileReference = $parser->parseFile($filePath);

        $astClassReferences = $astFileReference->classLikeReferences;

        self::assertCount(3, $astClassReferences);
        self::assertCount(0, $astClassReferences[0]->dependencies);
        self::assertCount(0, $astClassReferences[1]->dependencies);
        self::assertCount(2, $astClassReferences[2]->dependencies);

        $dependencies = $astClassReferences[2]->dependencies;

        self::assertSame(
            'Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\ClassA',
            $dependencies[0]->token->toString()
        );
        self::assertSame($filePath, $dependencies[0]->context->fileOccurrence->filepath);
        self::assertSame(19, $dependencies[0]->context->fileOccurrence->line);
        self::assertSame('anonymous_class_extends', $dependencies[0]->context->dependencyType->value);

        self::assertSame(
            'Tests\Deptrac\Deptrac\Core\Ast\Parser\Fixtures\InterfaceC',
            $dependencies[1]->token->toString()
        );
        self::assertSame($filePath, $dependencies[1]->context->fileOccurrence->filepath);
        self::assertSame(19, $dependencies[1]->context->fileOccurrence->line);
        self::assertSame('anonymous_class_implements', $dependencies[1]->context->dependencyType->value);
    }
}
