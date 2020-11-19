<?php


namespace makadev\RE2DFA\FiniteAutomaton;

use Generator;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\CharacterSet\DisjointAlphaSets;
use makadev\RE2DFA\EdgeListGraph\ELGEdgeList;
use makadev\RE2DFA\NodeSet\NodeSet;

class AlphaTransitionList {
    /**
     * edges (transitions)
     *
     * @var ELGEdgeList
     */
    private ELGEdgeList $transitions;

    /**
     * AlphaTransitionList constructor.
     */
    public function __construct() {
        $this->transitions = new ELGEdgeList();
    }

    /**
     * copy transitions from given list into this list
     *
     * @param AlphaTransitionList $other
     * @param int $rebase
     * @param bool $append whether to simply append (no checks for duplicates) or not
     * @param bool $safe whether iteration, f.e. for duplicate checks, is performed safely (using immutable copy)
     */
    public function copyFrom(AlphaTransitionList $other, int $rebase = 0, bool $append = true, bool $safe = true): void {
        $this->transitions->copyFrom($other->transitions, $rebase, $append, $safe);
    }

    /**
     * add a transition
     *
     * @param int $fromNode
     * @param int $toNode
     * @param AlphaSet $alphaSet
     * @param bool $append whether to simply append (no checks for duplicates) or not
     * @param bool $safe whether iteration, f.e. for duplicate checks, is performed safely (using immutable copy)
     */
    public function addTransition(int $fromNode, int $toNode, AlphaSet $alphaSet, bool $append = false, bool $safe = true): void {
        $transition = new AlphaTransition($fromNode, $toNode, new AlphaSetPayload($alphaSet));
        $this->transitions->add($transition, $append, $safe);
    }

    /**
     * get count of transitions in this list
     *
     * @return int
     */
    public function count(): int {
        return $this->transitions->count();
    }

    /**
     * return an enumerator (generator) which yields each transition
     *
     * @param bool $safe whether internal list iteration should be safe against mutation or not,
     * for concurrent iterations this MUST be true or it will result in endless loops or unexpected behavior
     * @return Generator<AlphaTransition>
     */
    public function enumerator(bool $safe = true): Generator {
        /**
         * @var Generator<AlphaTransition> $gen
         */
        $gen = $this->transitions->enumerator($safe);
        return $gen;
    }

    /**
     * Create AlphaSet Partitions such that each AlphaSet is either equal to OR contained
     * by each transitions AlphaSet. This is an optimization to greatly reduce the number of
     * characters (or AlphaSets) to be tested for transitions.
     *
     * @param NodeSet $nodeSet
     * @return DisjointAlphaSets
     */
    public function getDisjointAlphas(NodeSet $nodeSet): DisjointAlphaSets {
        $disjointAlphaSets = new DisjointAlphaSets();
        /**
         * @var AlphaTransition $transition
         */
        foreach ($this->enumerator() as $transition) {
            /**
             * @var int $node
             */
            foreach ($nodeSet->enumerator() as $node) {
                if ($transition->getFromNode() === $node) {
                    $disjointAlphaSets->addAlpha($transition->getAlphaSet());
                }
            }
        }
        return $disjointAlphaSets;
    }
}