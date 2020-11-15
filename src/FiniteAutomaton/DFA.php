<?php

namespace makadev\RE2DFA\FiniteAutomaton;

use makadev\RE2DFA\FiniteAutomaton;
use makadev\RE2DFA\NodeSet\NodeAllocator;
use SplFixedArray;

class DFA {

    /**
     *
     * @var int
     */
    private int $startNode;

    /**
     * @return int
     */
    public function getStartNode(): int {
        return $this->startNode;
    }

    /**
     *
     * @var SplFixedArray<DFAFixedNode>
     */
    private SplFixedArray $nodes;

    /**
     * DFA constructor.
     *
     * @param NodeAllocator $allocator
     * @param int $start
     * @param array<array> $finalStates
     * @param AlphaTransitionList $transitions
     */
    public function __construct(NodeAllocator $allocator, int $start, array $finalStates, AlphaTransitionList $transitions) {
        // startnode
        $this->startNode = $start;
        // create node lookup table
        $nodeTable = new SplFixedArray($allocator->allocations());
        for ($i = 0; $i < $allocator->allocations(); $i++) {
            $nodeTable[$i] = new FiniteAutomaton\DFAFixedNode();
        }
        $this->nodes = $nodeTable;
        // save final states
        /**
         * @var string $finalName
         * @var int[] $finalNodes
         */
        foreach ($finalStates as $finalName => $finalNodes) {
            if (count($finalNodes) > 0) {
                foreach ($finalNodes as $finalNode) {
                    /**
                     * @var DFAFixedNode $nodeObj
                     */
                    $nodeObj = $this->nodes[$finalNode];
                    if ($nodeObj->finalStates !== null) {
                        $nodeObj->finalStates->setSize($nodeObj->finalStates->getSize() + 1);
                    } else {
                        $nodeObj->finalStates = new SplFixedArray(1);
                    }
                    $nodeObj->finalStates[$nodeObj->finalStates->getSize() - 1] = $finalName;
                }
            }
        }
        // save transition
        /**
         * @var AlphaTransition $transition
         */
        foreach ($transitions->enumerator() as $transition) {
            /**
             * @var DFAFixedNode $node
             */
            $node = $this->nodes[$transition->getFromNode()];
            $node->transitions->push(new FiniteAutomaton\DFAFixedNodeTransition(clone $transition->getAlphaSet(), $transition->getToNode()));
        }
    }

    /**
     *
     *
     * @param int $currentNode
     * @param int $alpha
     * @return int|null
     */
    public function getNextFor(int $currentNode, int $alpha): ?int {
        /**
         *
         * @var DFAFixedNode $nodeObj
         */
        $nodeObj = $this->nodes[$currentNode];
        $transitions = $nodeObj->transitions;
        for ($transitions->rewind(); $transitions->valid(); $transitions->next()) {
            /**
             * @var DFAFixedNodeTransition $transition
             */
            $transition = $transitions->current();
            if ($transition->transitionSet->test($alpha)) {
                return $transition->targetNode;
            }
        }
        return null;
    }

    /**
     *
     *
     * @param int $currentNode
     * @return bool
     */
    public function isFinal(int $currentNode): bool {
        /**
         *
         * @var DFAFixedNode $nodeObj
         */
        $nodeObj = $this->nodes[$currentNode];
        return $nodeObj->finalStates !== null && ($nodeObj->finalStates->count() > 0);
    }

    /**
     *
     *
     * @param int $currentNode
     * @return SplFixedArray<string>
     */
    public function getFinalStates(int $currentNode): SplFixedArray {
        /**
         *
         * @var DFAFixedNode $nodeObj
         */
        $nodeObj = $this->nodes[$currentNode];
        if ($nodeObj->finalStates !== null && ($nodeObj->finalStates->count() > 0)) {
            return clone $nodeObj->finalStates;
        }
        return new SplFixedArray();
    }

    /**
     *
     *
     * @return DFARunner
     */
    public function newRunner(): DFARunner {
        return new DFARunner($this);
    }

    /**
     *
     *
     * @return string
     */
    public function __toString(): string {
        $result = "DFA: " . PHP_EOL;
        for ($this->nodes->rewind(); $this->nodes->valid(); $this->nodes->next()) {
            /**
             * @var DFAFixedNode $node
             */
            $node = $this->nodes->current();
            $id = $this->nodes->key();
            $result .= "  From Node " . $id . (($id === $this->startNode) ? ' (START)' : '') . PHP_EOL;
            if ($this->isFinal($id)) {
                $result .= "  FINAL: [";
                $fins = $this->getFinalStates($id);
                $first = true;
                for ($fins->rewind(); $fins->valid(); $fins->next()) {
                    $result .= $fins->current() . $first ? '' : ',';
                    $first = false;
                }
                $result .= "]" . PHP_EOL;
            }
            $trans = $node->transitions;
            for ($trans->rewind(); $trans->valid(); $trans->next()) {
                /**
                 * @var DFAFixedNodeTransition $t
                 */
                $t = $trans->current();
                $result .= "    With Alpha " . (string)$t->transitionSet . PHP_EOL;
                $result .= "      To Node " . $t->targetNode . PHP_EOL;
            }
        }
        $result .= "END OF DFA";
        return $result;
    }
}
