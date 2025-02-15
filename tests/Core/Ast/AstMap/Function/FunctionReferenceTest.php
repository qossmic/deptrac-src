<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\AstMap\Function;

use Deptrac\Deptrac\Contract\Ast\TaggedTokenReferenceInterface;
use Deptrac\Deptrac\Core\Ast\AstMap\Function\FunctionReference;
use Deptrac\Deptrac\Core\Ast\AstMap\Function\FunctionToken;
use PHPUnit\Framework\TestCase;
use Tests\Deptrac\Deptrac\Core\Ast\AstMap\TaggedTokenReferenceTestTrait;

final class FunctionReferenceTest extends TestCase
{
    use TaggedTokenReferenceTestTrait;

    private function newWithTags(array $tags): TaggedTokenReferenceInterface
    {
        return new FunctionReference(
            FunctionToken::fromFQCN('testing'),
            [],
            $tags
        );
    }
}
