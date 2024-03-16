<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\Function\FunctionReference;
use Qossmic\Deptrac\Core\Ast\AstMap\Function\FunctionToken;
use Qossmic\Deptrac\Core\Layer\Collector\PhpInternalCollector;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;

final class PHPInternalCollectorTest extends TestCase
{
    /**
     * @return iterable<array{array{value:string}, TokenReferenceInterface, bool}>
     */
    public static function provideSatisfy(): iterable
    {
        yield [['value' => '^PDO'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOException')), true];
        yield [['value' => '^PFO'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOException')), false];
        yield [['value' => '.*'], new ClassLikeReference(ClassLikeToken::fromFQCN('PDOExceptionNonExistent')), false];
        yield [['value' => '^pdo'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers')), true];
        yield [['value' => '^pfo'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers')), false];
        yield [['value' => '.*'], new FunctionReference(FunctionToken::fromFQCN('pdo_drivers_non_existent')), false];
    }

    /**
     * @dataProvider provideSatisfy
     */
    public function testSatisfy(array $config, TokenReferenceInterface $reference, bool $expected): void
    {
        $collector = new PhpInternalCollector();
        $actual = $collector->satisfy(
            $config,
            $reference,
            $this->createMock(LayerResolverInterface::class),
        );

        self::assertSame($expected, $actual);
    }
}
