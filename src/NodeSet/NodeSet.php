<?php


namespace makadev\RE2DFA\NodeSet;

use Generator;
use makadev\BitSet\BitSet;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\FiniteAutomaton\AlphaTransition;
use makadev\RE2DFA\FiniteAutomaton\AlphaTransitionList;
use makadev\RE2DFA\FiniteAutomaton\EpsilonTransition;
use makadev\RE2DFA\FiniteAutomaton\EpsilonTransitionList;
use RuntimeException;

class NodeSet {

    /**
     * internal bitmap
     *
     * @var BitSet
     */
    private BitSet $internalBitmap;

    /**
     * count to keep track of actual inserted/removed nodes
     *
     * @var int
     */
    private int $count;

    /**
     * NodeSet constructor.
     *
     * @param int $size
     */
    public function __construct(int $size) {
        $this->internalBitmap = new BitSet($size);
        $this->count = 0;
    }

    /**
     *
     */
    public function __clone() {
        $this->internalBitmap = clone $this->internalBitmap;
    }

    /**
     * add all reachable nodes for the given epsilon transition list
     *
     * @param EpsilonTransitionList $epsilonTransitionList
     */
    public function addReachableWithEpsilon(EpsilonTransitionList $epsilonTransitionList): void {
        do {
            $modified = false;
            foreach ($epsilonTransitionList->enumerator() as $transition) {
                /**
                 * @var EpsilonTransition $transition
                 */
                if ($this->isIn($transition->getFromNode())) {
                    if (!$this->isIn($transition->getToNode())) {
                        $this->add($transition->getToNode());
                        $modified = true;
                    }
                }
            }
        } while ($modified);
    }

    /**
     * add all nodes which are reachable from a given source set using given alpha and transitions
     *
     * @param NodeSet $fromSet
     * @param AlphaTransitionList $alphaTransitionList
     * @param AlphaSet $alphaSet
     */
    public function addReachableWithAlpha(NodeSet $fromSet, AlphaTransitionList $alphaTransitionList, AlphaSet $alphaSet): void {
        do {
            $modified = false;
            foreach ($alphaTransitionList->enumerator() as $transition) {
                /**
                 * @var AlphaTransition $transition
                 */
                if ($fromSet->isIn($transition->getFromNode())) {
                    if (!$this->isIn($transition->getToNode())) {
                        if ($transition->getAlphaSet()->contains($alphaSet)) {
                            $this->add($transition->getToNode());
                            $modified = true;
                        } else {
                            assert($transition->getAlphaSet()->isDisjoint($alphaSet));
                        }
                    }
                }
            }
        } while ($modified);
    }

    /**
     * get a representative, in this case simply the node with the lowes id in set
     *
     * @return int
     */
    public function getRepresentative(): int {
        assert($this->count() > 0);
        foreach ($this->enumerator() as $node) {
            // first node = lowest node
            return $node;
        }
        throw new RuntimeException("Unexpected empty set");
    }

    /**
     * add a node
     *
     * @param int $node
     */
    public function add(int $node): void {
        if ($this->internalBitmap->set($node)) {
            $this->count++;
        }
    }

    /**
     * remove a node
     *
     * @param int $node
     */
    public function delete(int $node): void {
        if ($this->internalBitmap->unset($node)) {
            $this->count--;
        }
    }

    /**
     * check if given node is in this set
     *
     * @param int $node
     * @return bool
     */
    public function isIn(int $node): bool {
        return $this->internalBitmap->test($node);
    }

    /**
     * check if given set is equal to this set
     *
     * @param NodeSet $other
     * @return bool
     */
    public function isEqual(NodeSet $other): bool {
        return $this->count === $other->count &&
            $this->internalBitmap->equals($other->internalBitmap);
    }

    /**
     * get number of nodes in this set
     *
     * @return int
     */
    public function count(): int {
        return $this->count;
    }

    /**
     * return a generator which yields every node in the set (in ascending order of node ids)
     *
     * @return Generator<int>
     */
    public function enumerator(): Generator {
        for ($w = 0; $w < $this->internalBitmap->getWordLength(); $w++) {
            $bitBlock = $this->internalBitmap->getBlock($w);
            if ($bitBlock !== 0) {
                $rangeStart = $w * $this->internalBitmap::BitPerWord;
                $rangeEnd = ($rangeStart + $this->internalBitmap::BitPerWord) - 1;
                if ($rangeEnd >= $this->internalBitmap->getBitLength()) {
                    $rangeEnd = $this->internalBitmap->getBitLength() - 1;
                }
                for ($bit = $rangeStart; $bit <= $rangeEnd; $bit++) {
                    if ($this->isIn($bit)) {
                        yield $bit;
                    }
                }
            }
        }
    }
}