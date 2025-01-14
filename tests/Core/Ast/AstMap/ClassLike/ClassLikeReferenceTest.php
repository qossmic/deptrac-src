<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\AstMap\ClassLike;

use PHPUnit\Framework\TestCase;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeReference;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeToken;
use Deptrac\Deptrac\Contract\Ast\AstMap\ClassLikeType;
use Deptrac\Deptrac\Contract\Ast\AstMap\TaggedTokenReferenceInterface;
use Tests\Deptrac\Deptrac\Core\Ast\AstMap\TaggedTokenReferenceTestTrait;

final class ClassLikeReferenceTest extends TestCase
{
    use TaggedTokenReferenceTestTrait;

    private function newWithTags(array $tags): TaggedTokenReferenceInterface
    {
        return new ClassLikeReference(
            ClassLikeToken::fromFQCN('Test'),
            ClassLikeType::TYPE_CLASS,
            [],
            [],
            $tags
        );
    }
}
