<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Core\Ast\Parser\Cache;

use Qossmic\Deptrac\Contract\Ast\AstFileReferenceCacheInterface;
use Qossmic\Deptrac\Contract\Ast\AstMap\FileReference;

final class AstFileReferenceInMemoryCache implements AstFileReferenceCacheInterface
{
    /**
     * @var array<string, FileReference>
     */
    private array $cache = [];

    public function get(string $filepath): ?FileReference
    {
        $filepath = realpath($filepath);

        return $this->cache[$filepath] ?? null;
    }

    public function set(FileReference $fileReference): void
    {
        $filepath = (string) realpath($fileReference->filepath);

        $this->cache[$filepath] = $fileReference;
    }
}
