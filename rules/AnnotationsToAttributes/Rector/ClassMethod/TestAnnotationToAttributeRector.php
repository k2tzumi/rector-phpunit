<?php

declare(strict_types=1);

namespace Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://docs.phpunit.de/en/10.5/attributes.html#test
 *
 * @see \Rector\PHPUnit\Tests\AnnotationsToAttributes\Rector\ClassMethod\TestAnnotationToAttributeRector\TestAnnotationToAttributeRectorTest
 */
final class TestAnnotationToAttributeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory,
        private readonly PhpDocTagRemover $phpDocTagRemover,
        private readonly ReflectionResolver $reflectionResolver,
        private readonly DocBlockUpdater $docBlockUpdater,
        private readonly PhpDocInfoFactory $phpDocInfoFactory
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change @test annotation to #[Test] attribute',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeFixture extends TestCase
{
    /**
     * @test
     */
    public function initialBalanceShouldBe0(): void
    {
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class SomeFixture extends TestCase
{
    #[Test]
    public function initialBalanceShouldBe0(): void
    {
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (! $phpDocInfo instanceof PhpDocInfo) {
            return null;
        }

        /** @var PhpDocTagNode[] $desiredTagValueNodes */
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('test');
        if ($desiredTagValueNodes === []) {
            return null;
        }

        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        if (! $classReflection->isClass()) {
            return null;
        }

        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (! $desiredTagValueNode->value instanceof GenericTagValueNode) {
                continue;
            }

            $node->attrGroups[] = $this->phpAttributeGroupFactory->createFromClassWithItems(
                'PHPUnit\Framework\Attributes\Test',
                []
            );

            // cleanup
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
        }

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
}
