<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\AstMap\ClassLike;

use Deptrac\Deptrac\Contract\Ast\TaggedTokenReferenceInterface;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Deptrac\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;
use PHPUnit\Framework\TestCase;
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
