<?php


namespace makadev\RE2DFA\NodeSet;

/**
 * Class NodeAllocator
 *
 * @package makadev\RE2DFA\NodeSet
 */
class NodeAllocator {
    /**
     * Last allocated node (might be lower than first)
     *
     * @var int
     */
    private int $last;

    /**
     * NodeAllocator constructor.
     *
     */
    public function __construct() {
        $this->last = -1;
    }


    /**
     * allocate a new node(s)
     *
     * @param int $amount
     * @return int
     */
    public function allocate(int $amount = 1): int {
        assert($amount >= 0);
        $this->last += $amount;
        return $this->last;
    }

    /**
     * get last allocated node
     *
     * @return int
     */
    public function getLast(): int {
        return $this->last;
    }

    /**
     * Nr. of nodes allocated by this allocator
     *
     * @return int
     */
    public function allocations(): int {
        return $this->last + 1;
    }
}
