<?php


use makadev\RE2DFA\NodeSet\NodeAllocator;
use makadev\RE2DFA\NodeSet\NodeSet;
use PHPUnit\Framework\TestCase;

class NodeSetTest extends TestCase {

    public function testEmptyNodeSetIsEmpty(): void {
        $allocator = new NodeAllocator();
        $allocator->allocate(100);
        $new = new NodeSet($allocator->allocations());
        $this->assertEquals(0, $new->count());
        $nodes = iterator_to_array($new->enumerator());
        $this->assertCount(0, $nodes);
    }

    public function testNodeSetAddRemove(): void {
        $allocator = new NodeAllocator();
        $allocator->allocate(100);
        $new = new NodeSet($allocator->allocations());
        $this->assertEquals(0, $new->count());
        $nodes = iterator_to_array($new->enumerator());
        $this->assertCount(0, $nodes);

        $new->add(0);
        $new->add(99);
        $this->assertEquals(2, $new->count());
        $nodes = iterator_to_array($new->enumerator());
        $this->assertCount(2, $nodes);
        $this->assertEquals(0, $nodes[0]);
        $this->assertEquals(99, $nodes[1]);

        $new->delete(0);
        $new->delete(99);
        $this->assertEquals(0, $new->count());
        $nodes = iterator_to_array($new->enumerator());
        $this->assertCount(0, $nodes);
    }

    public function testNodeSetEqual(): void {
        $allocator = new NodeAllocator();
        $allocator->allocate(100);
        $new = new NodeSet($allocator->allocations());
        $new->add(0);
        $new->add(99);

        $other = new NodeSet($allocator->allocations());
        $this->assertFalse($new->isEqual($other));

        $other->add(0);
        $other->add(99);
        $this->assertTrue($new->isEqual($other));
    }

    public function testNodeSetRepresentative(): void {
        $allocator = new NodeAllocator();
        $allocator->allocate(100);
        $new = new NodeSet($allocator->allocations());
        $new->add(5);
        $new->add(23);
        $this->assertEquals(5, $new->getRepresentative());
        $new->delete(5);
        $this->assertEquals(23, $new->getRepresentative());
    }
}