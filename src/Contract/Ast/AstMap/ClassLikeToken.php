<?php

declare(strict_types=1);

namespace Deptrac\Deptrac\Contract\Ast\AstMap;

/**
 * @psalm-immutable
 */
final class ClassLikeToken implements TokenInterface
{
    private function __construct(private readonly string $className) {}

    public static function fromFQCN(string $className): self
    {
        return new self(ltrim($className, '\\'));
    }

    public function match(string $pattern): bool
    {
        return 1 === preg_match($pattern, $this->className);
    }

    public function toString(): string
    {
        return $this->className;
    }

    public function equals(ClassLikeToken $classLikeName): bool
    {
        return $this->className === $classLikeName->className;
    }
}
