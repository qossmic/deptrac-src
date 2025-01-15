<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Layer\Collector;

use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Deptrac\Deptrac\Contract\Ast\AstMap\DependencyType;
use Deptrac\Deptrac\Core\Ast\AstMap\FileReferenceBuilder;
use Deptrac\Deptrac\Core\Layer\Collector\AttributeCollector;
use PHPUnit\Framework\TestCase;

final class AttributeCollectorTest extends TestCase
{
    private AttributeCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = new AttributeCollector();
    }

    public static function dataProviderSatisfy(): iterable
    {
        yield 'matches usage of attribute with only partial name' => [
            ['value' => 'MyAttribute'],
            true,
        ];
        yield 'does not match unescaped fully qualified class name' => [
            ['value' => 'App\MyAttribute'],
            true,
        ];
        yield 'does not match other attributes' => [
            ['value' => 'OtherAttribute'],
            false,
        ];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $config, bool $expected): void
    {
        $classLikeReference = FileReferenceBuilder::create('Foo.php')
            ->newClass('App\Foo', [], [])
            ->dependency(ClassLikeToken::fromFQCN('App\MyAttribute'), 2, DependencyType::ATTRIBUTE)
            ->dependency(ClassLikeToken::fromFQCN('MyAttribute'), 3, DependencyType::ATTRIBUTE)
            ->build()
        ;
        $actual = $this->collector->satisfy($config, $classLikeReference);

        self::assertSame($expected, $actual);
    }
}
