<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser\Extractors;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Qossmic\Deptrac\Core\Ast\AstMap\ReferenceBuilder;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicTypeResolver;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\TypeScope;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanContainerDecorator;
use Qossmic\Deptrac\Core\Ast\Parser\PhpStanParser\PhpStanTypeResolver;

/**
 * @implements ReferenceExtractorInterface<ClassLike>
 */
class ClassLikeExtractor implements ReferenceExtractorInterface
{
    private readonly Lexer $lexer;
    private readonly PhpDocParser $docParser;

    public function __construct(
        private readonly PhpStanContainerDecorator $phpStanContainer,
        private readonly PhpStanTypeResolver $phpStanTypeResolver,
        private readonly NikicTypeResolver $typeResolver
    ) {
        $this->lexer = new Lexer();
        $this->docParser = new PhpDocParser(new TypeParser(), new ConstExprParser());
    }

    public function processNodeWithClassicScope(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $attribute->name) as $classLikeName) {
                    $referenceBuilder->attribute($classLikeName, $attribute->getLine());
                }
            }
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));
        $docNode = $this->docParser->parse($tokens);
        $templateTypes = array_merge(
            array_map(
                static fn (TemplateTagValueNode $node): string => $node->name,
                $docNode->getTemplateTagValues()
            ),
            $referenceBuilder->getTokenTemplates()
        );

        foreach ($docNode->getMethodTagValues() as $methodTagValue) {
            foreach ($methodTagValue->parameters as $tag) {
                if (null !== $tag->type) {
                    $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

                    foreach ($types as $type) {
                        $referenceBuilder->parameter($type, $node->getStartLine());
                    }
                }
            }
            $returnType = $methodTagValue->returnType;
            if (null !== $returnType) {
                $types = $this->typeResolver->resolvePHPStanDocParserType($returnType, $typeScope, $templateTypes);

                foreach ($types as $type) {
                    $referenceBuilder->returnType($type, $node->getStartLine());
                }
            }
        }

        /** @var list<PropertyTagValueNode> $propertyTags */
        $propertyTags = array_merge($docNode->getPropertyTagValues(), $docNode->getPropertyReadTagValues(), $docNode->getPropertyWriteTagValues());
        foreach ($propertyTags as $tag) {
            $types = $this->typeResolver->resolvePHPStanDocParserType($tag->type, $typeScope, $templateTypes);

            foreach ($types as $type) {
                $referenceBuilder->variable($type, $node->getStartLine());
            }
        }
    }

    public function processNodeWithPhpStanScope(Node $node, ReferenceBuilder $referenceBuilder, Scope $scope): void
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                foreach ($this->phpStanTypeResolver->resolveType($attribute->name, $scope) as $classLikeName) {
                    $referenceBuilder->attribute($classLikeName, $attribute->getLine());
                }
            }
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return;
        }

        $fileTypeMapper = $this->phpStanContainer->createFileTypeMapper();
        $classReflection = $scope->getClassReflection();
        assert(null !== $classReflection);

        $resolvedPhpDoc = $fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->getTraitReflection()?->getName(),
            $scope->getFunction()?->getName(),
            $docComment->getText(),
        );

        foreach ($resolvedPhpDoc->getMethodTags() as $methodTag) {
            foreach ($methodTag->getParameters() as $methodTagParameter) {
                foreach ($methodTagParameter->getType()->getReferencedClasses() as $referencedClass) {
                    $referenceBuilder->parameter($referencedClass, $node->getStartLine());
                }
            }
            foreach ($methodTag->getReturnType()->getReferencedClasses() as $referencedClass) {
                $referenceBuilder->returnType($referencedClass, $node->getStartLine());
            }
        }

        foreach ($resolvedPhpDoc->getPropertyTags() as $propertyTag) {

            $referencedClasses = array_merge(
                $propertyTag->getReadableType()?->getReferencedClasses() ?? [],
                $propertyTag->getWritableType()?->getReferencedClasses() ?? [],
            );
            foreach (array_unique($referencedClasses) as $referencedClass) {
                $referenceBuilder->variable($referencedClass, $node->getStartLine());
            }
        }
    }

    public function getNodeType(): string
    {
        return ClassLike::class;
    }
}
