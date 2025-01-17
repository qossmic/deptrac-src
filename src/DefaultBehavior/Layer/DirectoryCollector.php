<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\DefaultBehavior\Layer;

use Deptrac\Deptrac\Contract\Ast\AstMap\TokenReferenceInterface;
use Deptrac\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Deptrac\Deptrac\DefaultBehavior\Layer\Helpers\RegexCollector;
use Symfony\Component\Filesystem\Path;

final class DirectoryCollector extends RegexCollector
{
    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        $filepath = $reference->getFilepath();

        if (null === $filepath) {
            return false;
        }

        $validatedPattern = $this->getValidatedPattern($config);
        $normalizedPath = Path::normalize($filepath);

        return 1 === preg_match($validatedPattern, $normalizedPath);
    }

    protected function getPattern(array $config): string
    {
        if (!isset($config['value']) || !is_string($config['value'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('DirectoryCollector needs the regex configuration.');
        }

        return '#'.$config['value'].'#i';
    }
}
