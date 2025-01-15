<?php

namespace Deptrac\Deptrac\Core\Ast\Parser\Cache;

use Qossmic\Deptrac\Contract\Ast\AstFileReferenceCacheInterface;

interface AstFileReferenceDeferredCacheInterface extends AstFileReferenceCacheInterface
{
    public function load(): void;

    public function write(): void;
}
