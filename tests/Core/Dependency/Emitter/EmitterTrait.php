<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Dependency\Emitter;

use Deptrac\Deptrac\Contract\Dependency\DependencyInterface;
use Deptrac\Deptrac\Core\Ast\AstLoader;
use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\FunctionCallResolver;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\FunctionLikeExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\KeywordExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\PropertyExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\StaticExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\VariableExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\TypeResolver;
use Deptrac\Deptrac\Core\Dependency\DependencyList;
use Deptrac\Deptrac\Core\Dependency\Emitter\DependencyEmitterInterface;
use PhpParser\ParserFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait EmitterTrait
{
    /**
     * @param string|string[] $files
     */
    public function getEmittedDependencies(DependencyEmitterInterface $emitter, $files): array
    {
        $files = (array) $files;

        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForNewestSupportedVersion(),
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            [
                new AnonymousClassExtractor(),
                new FunctionLikeExtractor($typeResolver),
                new PropertyExtractor($typeResolver),
                new KeywordExtractor($typeResolver),
                new StaticExtractor($typeResolver),
                new FunctionCallResolver($typeResolver),
                new VariableExtractor(),
            ]
        );
        $astMap = (new AstLoader($parser, new EventDispatcher()))->createAstMap($files);
        $result = new DependencyList();

        $emitter->applyDependencies($astMap, $result);

        return array_map(
            static function (DependencyInterface $d) {
                return sprintf('%s:%d on %s',
                    $d->getDepender()->toString(),
                    $d->getContext()->fileOccurrence->line,
                    $d->getDependent()->toString()
                );
            },
            $result->getDependenciesAndInheritDependencies()
        );
    }
}
