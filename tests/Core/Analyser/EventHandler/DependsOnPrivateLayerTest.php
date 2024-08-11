<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Analyser\EventHandler;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Analyser\AnalysisResult;
use Qossmic\Deptrac\Contract\Analyser\EventHelper;
use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;
use Qossmic\Deptrac\Contract\Ast\DependencyContext;
use Qossmic\Deptrac\Contract\Ast\DependencyType;
use Qossmic\Deptrac\Contract\Ast\FileOccurrence;
use Qossmic\Deptrac\Contract\Layer\LayerProvider;
use Qossmic\Deptrac\Contract\Result\Violation;
use Qossmic\Deptrac\Core\Analyser\EventHandler\DependsOnPrivateLayer;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;
use Qossmic\Deptrac\Core\Dependency\Dependency;

final class DependsOnPrivateLayerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = DependsOnPrivateLayer::getSubscribedEvents();

        self::assertCount(1, $subscribedEvents);
        self::assertArrayHasKey(ProcessEvent::class, $subscribedEvents);
        self::assertSame(['invoke', -3], $subscribedEvents[ProcessEvent::class]);
    }

    private function makeEvent(
        string $dependerLayer, string $dependentLayer, bool $isPublic
    ): ProcessEvent {
        $dependerToken = ClassLikeToken::fromFQCN('DependerClass');
        $dependentToken = ClassLikeToken::fromFQCN('DependentClass');

        return new ProcessEvent(
            new Dependency(
                $dependerToken,
                $dependentToken,
                new DependencyContext(new FileOccurrence('test', 1), DependencyType::STATIC_METHOD)
            ),
            new ClassLikeReference($dependerToken, ClassLikeType::TYPE_CLASS, [], [], []),
            $dependerLayer,
            new ClassLikeReference($dependentToken, ClassLikeType::TYPE_CLASS, [], [], []),
            [$dependentLayer => $isPublic],
            new AnalysisResult()
        );
    }

    public function testNoViolationsWhenDependentLayerIsPublic(): void
    {
        $helper = new EventHelper([], new LayerProvider([]));
        $handler = new DependsOnPrivateLayer($helper);

        $event = $this->makeEvent('DependerLayer', 'DependentLayer', true);
        $handler->invoke($event);

        $this->assertCount(
            0,
            $event->getResult()->rules(),
            'No violations should be added when dependent layer is public'
        );

        $this->assertFalse(
            $event->isPropagationStopped(),
            'Propagation should continue if dependent layer is public'
        );
    }

    public function testPropagationContinuesWhenPrivateLayerDependsOnItself(): void
    {
        $helper = new EventHelper([], new LayerProvider([]));
        $handler = new DependsOnPrivateLayer($helper);

        $event = $this->makeEvent('LayerA', 'LayerA', false);
        $handler->invoke($event);

        $this->assertCount(
            0,
            $event->getResult()->rules(),
            'No violations should be added when private layer depends on itself'
        );

        $this->assertFalse(
            $event->isPropagationStopped(),
            'Propagation should continue if private layer depends on itself'
        );
    }

    public function testPropagationContinuesWhenPublicLayerDependsOnItself(): void
    {
        $helper = new EventHelper([], new LayerProvider([]));
        $handler = new DependsOnPrivateLayer($helper);

        $event = $this->makeEvent('layerA', 'layerA', true);
        $handler->invoke($event);

        $this->assertCount(
            0,
            $event->getResult()->rules(),
            'No violations should be added when public layer depends on itself'
        );

        $this->assertFalse(
            $event->isPropagationStopped(),
            'Propagation should continue if public layer depends on itself'
        );
    }

    public function testPropagationStoppedWhenDependingOnPrivateLayer(): void
    {
        $helper = new EventHelper([], new LayerProvider([]));
        $handler = new DependsOnPrivateLayer($helper);

        $event = $this->makeEvent('DependerLayer', 'DependentLayer', false);
        $handler->invoke($event);

        $violations = $event->getResult()->rules()[Violation::class] ?? [];
        $this->assertCount(
            1,
            $violations,
            'Violation should be added when depending on private layer'
        );

        $rule = array_values($violations)[0];
        $this->assertSame(
            'DependerLayer',
            $rule->getDependerLayer(),
        );
        $this->assertSame(
            'DependentLayer',
            $rule->getDependentLayer(),
        );
        $this->assertSame(
            'DependsOnPrivateLayer',
            $rule->ruleName(),
        );

        $this->assertTrue(
            $event->isPropagationStopped(),
            'Propagation should stop if depending on private layer'
        );
    }
}
