<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Config\Collector\TagValueRegexConfig;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;
use Qossmic\Deptrac\Core\Layer\Collector\TagValueRegexCollector;
use Qossmic\Deptrac\Core\Layer\LayerResolverInterface;

final class TagValueRegexCollectorTest extends TestCase
{
    private TagValueRegexCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = new TagValueRegexCollector();
    }

    public static function dataProviderSatisfy(): iterable
    {
        yield 'match tag name, no value' => [
            TagValueRegexConfig::create('@foo'),
            ['@foo' => ['']],
        ];

        yield 'match tag name, any value' => [
            TagValueRegexConfig::create('@foo'),
            ['@foo' => ['anything']],
        ];

        yield 'match tag name and value' => [
            TagValueRegexConfig::create('@foo-bar', '/some/'),
            ['@xyz' => [''], '@foo-bar' => ['anything', 'something']],
        ];

        yield 'match value with anchored regex' => [
            TagValueRegexConfig::create('@foo-bar')->match('!thing$!'),
            ['@xyz' => [''], '@foo-bar' => ['anything', 'something']],
        ];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(TagValueRegexConfig $configuration, array $tags): void
    {
        $actual = $this->collector->satisfy(
            $configuration->toArray(),
            new ClassLikeReference(ClassLikeToken::fromFQCN('Dummy'), ClassLikeType::TYPE_CLASS, [], [], $tags),
            $this->createMock(LayerResolverInterface::class),
        );

        self::assertTrue($actual);
    }

    public static function dataProviderNotSatisfy(): iterable
    {
        yield 'tag name mismatch' => [
            TagValueRegexConfig::create('@foo'),
            ['@bar' => ['anything']],
        ];

        yield 'value mismatch' => [
            TagValueRegexConfig::create('@foo', '/something/'),
            ['@xyz' => [''], '@foo' => ['anything', 'another thing']],
        ];
        yield 'anchored regex' => [
            TagValueRegexConfig::create('@foo', '/^thing/'),
            ['@xyz' => [''], '@foo' => ['anything', 'another thing']],
        ];
    }

    /**
     * @dataProvider dataProviderNotSatisfy
     */
    public function testNotSatisfy(TagValueRegexConfig $configuration, array $tags): void
    {
        $actual = $this->collector->satisfy(
            $configuration->toArray(),
            new ClassLikeReference(ClassLikeToken::fromFQCN('Dummy'), ClassLikeType::TYPE_CLASS, [], [], $tags),
            $this->createMock(LayerResolverInterface::class),
        );

        self::assertFalse($actual);
    }

    public static function dataProviderBadConfig(): iterable
    {
        yield 'empty' => [[]];

        yield 'no tag name' => [['value' => '/.+/']];

        yield 'empty tag name' => [['tag' => '']];

        yield 'tag name with missing ampersand' => [['tag' => 'foo']];

        yield 'tag name with space' => [['tag' => '@foo bar']];

        yield 'non-string tag name' => [['tag' => 1234]];

        yield 'non-string regex value' => [['tag' => '@test', 'value' => 1234]];

        yield 'bad regex value' => [['tag' => '@test', 'value' => '(((]]]']];
    }

    /**
     * @dataProvider dataProviderBadConfig
     */
    public function testBadConfig($config): void
    {
        $this->expectException(InvalidCollectorDefinitionException::class);

        $this->collector->satisfy(
            $config,
            new ClassLikeReference(ClassLikeToken::fromFQCN('Foo')),
            $this->createMock(LayerResolverInterface::class),
        );
    }
}
