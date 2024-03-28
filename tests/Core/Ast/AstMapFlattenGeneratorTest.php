<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast;

use Closure;
use LogicException;
use PhpParser\Lexer;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\AstFileAnalysedEvent;
use Qossmic\Deptrac\Contract\Ast\AstFileSyntaxErrorEvent;
use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Ast\PostCreateAstMapEvent;
use Qossmic\Deptrac\Contract\Ast\PreCreateAstMapEvent;
use Qossmic\Deptrac\Core\Ast\AstLoader;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\ClassExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\InterfaceExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\ParserInterface;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanContainerDecorator;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanParser;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseA;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseC;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceA;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceD;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceE;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceA;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceD;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceE;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA1;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA2;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceB;
use Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceC;

final class AstMapFlattenGeneratorTest extends TestCase
{
    /**
     * @dataProvider createParser
     */
    public function testBasicInheritance(Closure $parserBuilder): void
    {
        $expectedEvents = [
            PreCreateAstMapEvent::class,
            AstFileAnalysedEvent::class,
            PostCreateAstMapEvent::class,
        ];

        $filePath = __DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritance.php';
        $parser = $parserBuilder($filePath);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader(
            $parser,
            $eventDispatcher
        );

        $astMap = $astLoader->createAstMap([$filePath]);

        $dispatchedEvents = $eventDispatcher->getOrphanedEvents();
        self::assertSame($expectedEvents, $dispatchedEvents);

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceA::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceB::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            ['Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB::7 (Extends))'],
            self::getInheritedInherits(FixtureBasicInheritanceC::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC::8 (Extends) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB::7 (Extends))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB::7 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC::8 (Extends))',
            ],
            self::getInheritedInherits(FixtureBasicInheritanceD::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceA::6 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceD::9 (Extends) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC::8 (Extends) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB::7 (Extends))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceB::7 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceD::9 (Extends) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC::8 (Extends))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceC::8 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceD::9 (Extends))',
            ],
            self::getInheritedInherits(FixtureBasicInheritanceE::class, $astMap)
        );
    }

    /**
     * @dataProvider createParser
     */
    public function testBasicInheritanceInterfaces(Closure $parserBuilder): void
    {
        $expectedEvents = [
            PreCreateAstMapEvent::class,
            AstFileAnalysedEvent::class,
            PostCreateAstMapEvent::class,
        ];

        $filePath = __DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceInterfaces.php';
        $parser = $parserBuilder($filePath);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader(
            $parser,
            $eventDispatcher
        );
        $astMap = $astLoader->createAstMap([$filePath]);

        $dispatchedEvents = $eventDispatcher->getOrphanedEvents();
        self::assertSame($expectedEvents, $dispatchedEvents);

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceInterfaceA::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceInterfaceB::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            ['Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB::7 (Implements))'],
            self::getInheritedInherits(FixtureBasicInheritanceInterfaceC::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC::8 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB::7 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC::8 (Implements))',
            ],
            self::getInheritedInherits(FixtureBasicInheritanceInterfaceD::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceA::6 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceD::9 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC::8 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB::7 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceB::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceD::9 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC::8 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceC::8 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\FixtureBasicInheritanceInterfaceD::9 (Implements))',
            ],
            self::getInheritedInherits(FixtureBasicInheritanceInterfaceE::class, $astMap)
        );
    }

    /**
     * @dataProvider createParser
     */
    public function testBasicMultipleInheritanceInterfaces(Closure $parserBuilder): void
    {
        $expectedEvents = [
            PreCreateAstMapEvent::class,
            AstFileAnalysedEvent::class,
            PostCreateAstMapEvent::class,
        ];

        $filePath = __DIR__.'/Fixtures/BasicInheritance/MultipleInheritanceInterfaces.php';
        $parser = $parserBuilder($filePath);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader(
            $parser,
            $eventDispatcher
        );
        $astMap = $astLoader->createAstMap([$filePath]);

        $dispatchedEvents = $eventDispatcher->getOrphanedEvents();
        self::assertSame($expectedEvents, $dispatchedEvents);

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(MultipleInteritanceA1::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(MultipleInteritanceA2::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(MultipleInteritanceA::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA1::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA2::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA::8 (Implements))',
            ],
            self::getInheritedInherits(MultipleInteritanceB::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA1::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceB::9 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA1::8 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceB::9 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA2::7 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceB::9 (Implements) -> Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA::8 (Implements))',
                'Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceA::8 (Implements) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\MultipleInteritanceB::9 (Implements))',
            ],
            self::getInheritedInherits(MultipleInteritanceC::class, $astMap)
        );
    }

    /**
     * @dataProvider createParser
     */
    public function testBasicMultipleInheritanceWithNoise(Closure $parserBuilder): void
    {
        $expectedEvents = [
            PreCreateAstMapEvent::class,
            AstFileAnalysedEvent::class,
            PostCreateAstMapEvent::class,
        ];

        $filePath = __DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceWithNoise.php';
        $parser = $parserBuilder($filePath);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader(
            $parser,
            $eventDispatcher
        );
        $astMap = $astLoader->createAstMap([$filePath]);

        $dispatchedEvents = $eventDispatcher->getOrphanedEvents();
        self::assertSame($expectedEvents, $dispatchedEvents);

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceWithNoiseA::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            [],
            self::getInheritedInherits(FixtureBasicInheritanceWithNoiseB::class, $astMap)
        );

        self::assertEqualsCanonicalizing(
            ['Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseA::18 (Extends) (path: Tests\Qossmic\Deptrac\Core\Ast\Fixtures\BasicInheritance\FixtureBasicInheritanceWithNoiseB::19 (Extends))'],
            self::getInheritedInherits(FixtureBasicInheritanceWithNoiseC::class, $astMap)
        );
    }

    public function testSkipsErrorsAndDispatchesErrorEventAndReturnsEmptyAstMap(): void
    {
        $expectedEvents = [
            PreCreateAstMapEvent::class,
            AstFileSyntaxErrorEvent::class,
            PostCreateAstMapEvent::class,
        ];
        $parser = $this->createMock(ParserInterface::class);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader($parser, $eventDispatcher);

        $parser
            ->expects(self::atLeastOnce())
            ->method('parseFile')
            ->with(__DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceWithNoise.php')
            ->willThrowException(new CouldNotParseFileException('Syntax Error'));

        $astLoader->createAstMap([__DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceWithNoise.php']);

        $dispatchedEvents = $eventDispatcher->getOrphanedEvents();
        self::assertSame($expectedEvents, $dispatchedEvents);
    }

    public function testThrowsOtherExceptions(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch()
        );
        $astLoader = new AstLoader($parser, $eventDispatcher);

        $parser
            ->expects(self::atLeastOnce())
            ->method('parseFile')
            ->with(__DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceWithNoise.php')
            ->willThrowException(new LogicException('Uncaught exception'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Uncaught exception');

        $astLoader->createAstMap([__DIR__.'/Fixtures/BasicInheritance/FixtureBasicInheritanceWithNoise.php']);
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
            new ClassExtractor(),
            new InterfaceExtractor(),
        ];

        return new PhpStanParser($phpStanContainer, $cache, $extractors);
    }

    public static function createNikicParser(string $filePath): NikicPhpParser
    {
        $cache = new AstFileReferenceInMemoryCache();
        $extractors = [
            new ClassExtractor(),
            new InterfaceExtractor(),
        ];

        return new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ), $cache, $extractors
        );
    }

    /**
     * @return list<string>
     */
    private static function getInheritedInherits(string $class, AstMap $astMap): array
    {
        $inherits = [];
        foreach ($astMap->getClassInherits(ClassLikeToken::fromFQCN($class)) as $v) {
            if (count($v->getPath()) > 0) {
                $inherits[] = (string) $v;
            }
        }

        return $inherits;
    }
}
