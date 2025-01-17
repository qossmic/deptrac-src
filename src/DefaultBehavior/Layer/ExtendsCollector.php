<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\DefaultBehavior\Layer;

use Qossmic\Deptrac\Contract\Ast\AstException;
use Deptrac\Deptrac\Contract\Ast\AstMap\AstInheritType;
use Deptrac\Deptrac\Contract\Ast\AstMap\AstMapInterface;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Deptrac\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Deptrac\Deptrac\Contract\Ast\AstMapExtractorInterface;
use Deptrac\Deptrac\Contract\Ast\CouldNotParseFileException;
use Deptrac\Deptrac\Contract\Layer\CollectorInterface;
use Deptrac\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;

final class ExtendsCollector implements CollectorInterface
{
    private AstMapInterface $astMap;

    public function __construct(private readonly AstMapExtractorInterface $astMapExtractor) {}

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!$reference instanceof ClassLikeReference) {
            return false;
        }

        $interfaceName = $this->getInterfaceName($config);

        try {
            $this->astMap ??= $this->astMapExtractor->extract();
        } catch (AstException $exception) {
            throw CouldNotParseFileException::because('Could not build Ast map', $exception);
        }
        foreach ($this->astMap->getClassInherits($reference->getToken()) as $inherit) {
            if (AstInheritType::EXTENDS === $inherit->type && $inherit->classLikeName->equals($interfaceName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool|string|array<string, string>> $config
     *
     * @throws InvalidCollectorDefinitionException
     */
    private function getInterfaceName(array $config): ClassLikeToken
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ExtendsCollector needs the interface or class name as a string.');
        }

        return ClassLikeToken::fromFQCN($config['value']);
    }
}
