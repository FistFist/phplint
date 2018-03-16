<?php
declare(strict_types=1);

namespace PhpLint\Test\Ast;

use PhpLint\Ast\AstNodeTraverser;
use PHPUnit\Framework\TestCase;

class AstNodeTraverserTest extends TestCase
{
    public function testGetParent()
    {
        $root = new TestNode('root');
        $rootChild0 = new TestNode('root_child0');
        $rootChild0->setAttribute(AstNodeTraverser::PARENT_ATTRIBUTE_NAME, $root);

        self::assertEqualsNode($root, AstNodeTraverser::getParent($rootChild0));
    }

    public function testGetChildren()
    {
        $rootChild0 = new TestNode('root_child0');
        $rootChild1 = new TestNode('root_child1');
        $root = new TestNode('root', [
            $rootChild0,
            $rootChild1,
        ]);

        $children = AstNodeTraverser::getChildren($root);
        self::assertCount(2, $children);
        self::assertEqualsNode($rootChild0, $children[0]);
        self::assertEqualsNode($rootChild1, $children[1]);
    }

    public function testGetSiblings()
    {
        $rootChild0 = new TestNode('root_child0');
        $rootChild1 = new TestNode('root_child1');
        $root = new TestNode('root', [
            $rootChild0,
            $rootChild1,
        ]);
        $rootChild0->setAttribute(AstNodeTraverser::PARENT_ATTRIBUTE_NAME, $root);
        $rootChild1->setAttribute(AstNodeTraverser::PARENT_ATTRIBUTE_NAME, $root);

        $rootSiblings = AstNodeTraverser::getSiblings($root);
        self::assertCount(0, $rootSiblings);

        $rootChild0Siblings = AstNodeTraverser::getSiblings($rootChild0);
        self::assertCount(1, $rootChild0Siblings);
        self::assertEqualsNode($rootChild1, $rootChild0Siblings[0]);

        $rootChild1Siblings = AstNodeTraverser::getSiblings($rootChild1);
        self::assertCount(1, $rootChild1Siblings);
        self::assertEqualsNode($rootChild0, $rootChild1Siblings[0]);
    }

    public function testCreateParentBackLinks()
    {
        $rootChild0Child0 = new TestNode('root_child1');
        $rootChild0 = new TestNode('root_child0', [
            $rootChild0Child0,
        ]);
        $root = new TestNode('root', [
            $rootChild0,
        ]);

        AstNodeTraverser::createParentBackLinks($root);
        self::assertNull(AstNodeTraverser::getParent($root));
        self::assertEqualsNode($root, AstNodeTraverser::getParent($rootChild0));
        self::assertEqualsNode($rootChild0, AstNodeTraverser::getParent($rootChild0Child0));
    }

    public function testTraversalIsDepthFirst()
    {
        $testTree = TestNode::createFromArrayDescription(
            'root',
            [
                'root_child0' => [
                    'root_child0_child0',
                    'root_child0_child1',
                ],
                'root_child1' => [
                    'root_child1_child0',
                ],
            ]
        );
        $traverser = new AstNodeTraverser($testTree);

        self::assertEqualsNode($testTree, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        $rootChild0 = AstNodeTraverser::getChildren($testTree)[0];
        self::assertEqualsNode($rootChild0, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        $rootChild0Child0 = AstNodeTraverser::getChildren($rootChild0)[0];
        self::assertEqualsNode($rootChild0Child0, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        $rootChild0Child1 = AstNodeTraverser::getChildren($rootChild0)[1];
        self::assertEqualsNode($rootChild0Child1, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        $rootChild1 = AstNodeTraverser::getChildren($testTree)[1];
        self::assertEqualsNode($rootChild1, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        $rootChild1Child0 = AstNodeTraverser::getChildren($rootChild1)[0];
        self::assertEqualsNode($rootChild1Child0, $traverser->current());
        self::assertTrue($traverser->valid());
        $traverser->next();

        // Reached the end of the tree
        self::assertFalse($traverser->valid());
    }

    public function testIsIterable()
    {
        $testTree = TestNode::createFromArrayDescription(
            'root',
            [
                'root_child0' => [
                    'root_child0_child0',
                    'root_child0_child1',
                ],
                'root_child1' => [
                    'root_child1_child0',
                ],
            ]
        );
        $traverser = new AstNodeTraverser($testTree);

        $rootChild0 = AstNodeTraverser::getChildren($testTree)[0];
        $rootChild0Child0 = AstNodeTraverser::getChildren($rootChild0)[0];
        $rootChild0Child1 = AstNodeTraverser::getChildren($rootChild0)[1];
        $rootChild1 = AstNodeTraverser::getChildren($testTree)[1];
        $rootChild1Child0 = AstNodeTraverser::getChildren($rootChild1)[0];

        $expectedNodes = [
            $testTree,
            $rootChild0,
            $rootChild0Child0,
            $rootChild0Child1,
            $rootChild1,
            $rootChild1Child0,
        ];
        $interatorCounter = 0;
        foreach ($traverser as $nodeIndex => $node) {
            self::assertEquals($interatorCounter, $nodeIndex);
            $interatorCounter += 1;
            $nextExpectedNode = array_shift($expectedNodes);
            self::assertEqualsNode($nextExpectedNode, $node);
        }
        self::assertEquals(6, $interatorCounter);
        self::assertEmpty($expectedNodes);
    }

    /**
     * @param TestNode $expectedNode
     * @param TestNode $actualNode
     */
    protected static function assertEqualsNode(TestNode $expectedNode, TestNode $actualNode)
    {
        self::assertTrue(
            $actualNode->equals($expectedNode),
            sprintf(
                'Failed asserting that actual node "%s" equals expected node "%s".',
                $actualNode->getId(),
                $expectedNode->getId()
            )
        );
    }
}
