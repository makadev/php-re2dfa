<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use Generator;
use makadev\RE2DFA\EdgeListGraph\ELGEdgeList;

class EpsilonTransitionList {
    /**
     * internal transition list
     *
     * @var ELGEdgeList
     */
    private ELGEdgeList $transitions;

    /**
     * EpsilonTransitionList constructor.
     */
    public function __construct() {
        $this->transitions = new ELGEdgeList();
    }

    /**
     * copy transitions from given list into this list
     *
     * @param EpsilonTransitionList $other
     * @param int $rebase
     * @param bool $append whether to simply append (no checks for duplicates) or not
     * @param bool $safe whether iteration, f.e. for duplicate checks, is performed safely (using immutable copy)
     */
    public function copyFrom(EpsilonTransitionList $other, int $rebase = 0, bool $append = true, bool $safe = true): void {
        $this->transitions->copyFrom($other->transitions, $rebase, $append, $safe);
    }

    /**
     * add transition
     *
     * @param int $fromNode
     * @param int $toNode
     * @param bool $append whether to simply append (no checks for duplicates) or not
     * @param bool $safe whether iteration, f.e. for duplicate checks, is performed safely (using immutable copy)
     */
    public function addTransition(int $fromNode, int $toNode, bool $append = false, bool $safe = true): void {
        $transition = new EpsilonTransition($fromNode, $toNode);
        $this->transitions->add($transition, $append, $safe);
    }

    /**
     * get number of transitions in this list
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
     * @return Generator<EpsilonTransition>
     */
    public function enumerator(bool $safe = true): Generator {
        /**
         * @var Generator<EpsilonTransition> $gen
         */
        $gen = $this->transitions->enumerator($safe);
        return $gen;
    }
}