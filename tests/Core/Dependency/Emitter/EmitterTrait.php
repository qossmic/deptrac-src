<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Dependency\Emitter;

use Deptrac\Deptrac\Contract\Dependency\DependencyInterface;
use Deptrac\Deptrac\Core\Ast\AstLoader;
use Deptrac\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\AnonymousClassExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\ClassExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\FunctionCallExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\FunctionLikeExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\InstanceofExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\NewExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\PropertyExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\StaticCallExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\StaticPropertyFetchExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\TraitUseExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\UseExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\Extractors\VariableExtractor;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Deptrac\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicTypeResolver;
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

        $nikicTypeResolver = new NikicTypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->createForNewestSupportedVersion(),
            new AstFileReferenceInMemoryCache(),
            [
                new AnonymousClassExtractor(),
                new FunctionLikeExtractor($nikicTypeResolver),
                new PropertyExtractor($nikicTypeResolver),
                new FunctionCallExtractor($nikicTypeResolver),
                new VariableExtractor($nikicTypeResolver),
                new ClassExtractor(),
                new UseExtractor(),
                new InstanceofExtractor($nikicTypeResolver),
                new StaticCallExtractor($nikicTypeResolver),
                new StaticPropertyFetchExtractor($nikicTypeResolver),
                new NewExtractor($nikicTypeResolver),
                new TraitUseExtractor($nikicTypeResolver),
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
