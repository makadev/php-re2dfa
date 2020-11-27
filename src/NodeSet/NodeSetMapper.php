<?php


namespace makadev\RE2DFA\NodeSet;


use RuntimeException;
use SplFixedArray;

class NodeSetMapper {

    /**
     * Allocator for the NodeSet Mapping allocating new nodes
     * for each node id -> node set mapping.
     *
     * @var NodeAllocator
     */
    private NodeAllocator $allocator;

    /**
     * the actual nodeset mapping
     *
     * @var SplFixedArray<NodeSet>
     */
    private SplFixedArray $mapping;

    /**
     * actual size of the node set mapping storage
     *
     * @var int
     */
    private int $size;

    /**
     *
     *
     * @param int $pos
     * @param NodeSet $element
     */
    private function internalAdd(int $pos, NodeSet $element): void {
        if ($pos >= $this->mapping->getSize()) {
            //TODO: might be better to use linear growth to reduce pressure
            // or a fan/tree structure which allows for allocation even in
            // fragmented memory
            if (!$this->mapping->setSize($this->mapping->getSize() * 2)) {
                throw new RuntimeException('Reallocation failed');
            }
        }
        assert($this->size === $pos);
        $this->size++;
        $this->mapping[$pos] = $element;
    }

    /**
     * NodeSetMapper constructor.
     *
     */
    public function __construct() {
        $this->allocator = new NodeAllocator();
        $this->mapping = new SplFixedArray(1024);
        $this->size = 0;
    }

    public function count(): int {
        return $this->size;
    }

    /**
     * get the node allocator
     *
     * @return NodeAllocator
     */
    public function getAllocator(): NodeAllocator {
        return $this->allocator;
    }

    /**
     * get the set for given node id
     *
     * @param int $node
     * @return NodeSet
     */
    public function getNodeSetFor(int $node): NodeSet {
        $res = $this->mapping[$node];
        assert($res !== null);
        return $res;
    }

    /**
     * add or find a nodeset
     *
     * @param NodeSet $ns
     * @param bool $unique
     * @return int
     */
    public function add(NodeSet $ns, bool $unique = true): int {
        if ($unique) {
            $res = $this->findSet($ns);
            if ($res !== null) {
                return $res;
            }
        }
        $res = $this->allocator->allocate();
        $this->internalAdd($res, $ns);
        return $res;
    }

    /**
     * Remove set for given Node, **this will reorder the set mapping and replace the removed node with the last node**,
     * effectively remapping the last node.
     *
     * @param integer $nodeID
     * @return void
     */
    public function remove(int $nodeID): void {
        // if $nodeID is not the last element, switch places with the last element and
        // simply reduce the array size
        if($nodeID < --$this->size) {
            $this->mapping[$nodeID] = $this->mapping[$this->size];
            $this->mapping[$this->size] = null;
        }
    }

    /**
     * find a nodeset
     *
     * @param NodeSet $nodeSet
     * @return int|null
     */
    public function findSet(NodeSet $nodeSet): ?int {
        $i = 0;
        for ($this->mapping->rewind(); $this->mapping->valid(); $this->mapping->next()) {
            if ($i >= $this->size) {
                return null;
            }
            /**
             * @var NodeSet $currentSet
             */
            $currentSet = $this->mapping->current();
            if ($currentSet->isEqual($nodeSet)) {
                $key = $this->mapping->key();
                return $key;
            }
            $i++;
        }
        return null;
    }

    /**
     * for a given node, find the representative set (the first containing the node, which should also be the only one)
     *
     * @param int $node
     * @return int|null
     */
    public function findRepresentativeSet(int $node): ?int {
        $i = 0;
        for ($this->mapping->rewind(); $this->mapping->valid(); $this->mapping->next()) {
            if ($i >= $this->size) {
                return null;
            }
            /**
             * @var NodeSet $nodeSet
             */
            $nodeSet = $this->mapping->current();
            if ($nodeSet->isIn($node)) {
                $key = $this->mapping->key();
                assert(is_int($key));
                return $key;
            }
            $i++;
        }
        return null;
    }

    /**
     * for a given node, find the representative set (the first containing the node, which should also be the only one)
     * and return it's representative node (the first node of the representative set)
     *
     * @param int $node
     * @return int|null
     */
    public function getRepresentative(int $node): ?int {
        $representative = $this->findRepresentativeSet($node);
        if ($representative) {
            /**
             * @var NodeSet $representativeSet
             */
            $representativeSet = $this->mapping[$representative];
            return $representativeSet->getRepresentative();
        }
        return null;
    }
}