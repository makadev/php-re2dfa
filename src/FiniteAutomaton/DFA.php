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
     * DFA constructor.
     *
     * @var SplFixedArray<DFAFixedNode>
     */
    private SplFixedArray $nodes;

    public function __construct(SplFixedArray $nodes, int $startNode)
    {
        $this->startNode = $startNode;
        $this->nodes = $nodes;
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
     * Access the DFA Nodes directly
     *
     * @return SplFixedArray<DFAFixedNode>
     */
    public function getNodes(): SplFixedArray {
        return $this->nodes;
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
