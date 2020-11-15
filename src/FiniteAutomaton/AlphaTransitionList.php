<?php


namespace makadev\RE2DFA\FiniteAutomaton;

use Generator;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\EdgeListGraph\ELGEdgeList;
use makadev\RE2DFA\NodeSet\NodeSet;
use SplDoublyLinkedList;
use SplQueue;

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
     * @return SplDoublyLinkedList<AlphaSet>
     */
    public function getDisjointAlphas(NodeSet $nodeSet): SplDoublyLinkedList {
        $emptySet = new AlphaSet();
        $disjointAlphaSet = new SplQueue();
        $queue = new SplQueue();
        /**
         * @var AlphaTransition $transition
         */
        foreach ($this->enumerator() as $transition) {
            /**
             * @var int $node
             */
            foreach ($nodeSet->enumerator() as $node) {
                if ($transition->getFromNode() === $node) {
                    $insertionSet = clone $transition->getAlphaSet();
                    while (!$disjointAlphaSet->isEmpty()) {
                        /**
                         * @var AlphaSet $currentSet
                         */
                        $currentSet = $disjointAlphaSet->dequeue();
                        // test 1.: set is already fully contained
                        if ($insertionSet->equals($currentSet)) {
                            $queue->enqueue($currentSet);
                            $insertionSet = $emptySet;
                            break;
                        }
                        // test 2.: skip if disjoint
                        if ($insertionSet->isDisjoint($currentSet)) {
                            $queue->enqueue($currentSet);
                            continue;
                        }
                        // 3.: otherwise break the sets apart into:
                        //  - a common part (nonempty since both sets where not disjoint in test 2)
                        //    and already known to be disjoint since it's in the disjoint set
                        //  - the rest of the current set which might be empty if insertionSet contains currentSet
                        //  - the rest of the insertion which might be empty if currentSet contains insertionSet
                        //    and needs to be checked against the other sets if not empty
                        $newInsertion = $insertionSet->subtract($currentSet);
                        $common = $insertionSet->intersect($currentSet);
                        $currentSet = $currentSet->subtract($insertionSet);
                        assert(!$common->isEmpty());
                        $queue->enqueue($common);
                        if (!$currentSet->isEmpty()) {
                            $queue->enqueue($currentSet);
                        }
                        if (!$newInsertion->isEmpty()) {
                            break;
                        }
                        $insertionSet = $newInsertion;
                    }
                    // add newly generated disjoint sets back to the disjoint AlphaSets
                    while (!$queue->isEmpty()) {
                        $disjointAlphaSet->push($queue->dequeue());
                    }
                    // add disjoint rest
                    if (!$insertionSet->isEmpty()) {
                        $disjointAlphaSet->push($insertionSet);
                    }
                }
            }
        }
        return $disjointAlphaSet;
    }
}