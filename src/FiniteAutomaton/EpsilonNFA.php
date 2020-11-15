<?php


namespace makadev\RE2DFA\FiniteAutomaton;

use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\NodeSet\NodeAllocator;
use SplFixedArray;

class EpsilonNFA {

    /**
     *
     * @var NodeAllocator
     */
    private NodeAllocator $allocator;

    /**
     * start node
     *
     * @var int
     */
    private int $startNode;

    /**
     * final node
     *
     * @var int
     */
    private int $finalNode;

    /**
     * epsilon transitions
     *
     * @var EpsilonTransitionList
     */
    private EpsilonTransitionList $epsilonTransitions;

    /**
     * non epsilon transitions
     *
     * @var AlphaTransitionList
     */
    private AlphaTransitionList $alphaTransitions;

    /**
     * EpsilonNFA constructor.
     *
     * f.e. [a...z]
     *
     * @param AlphaSet $alphaSet
     */
    public function __construct(AlphaSet $alphaSet) {
        $this->allocator = new NodeAllocator();
        $this->alphaTransitions = new AlphaTransitionList();
        $this->epsilonTransitions = new EpsilonTransitionList();
        $this->startNode = $this->allocator->allocate();
        $this->finalNode = $this->allocator->allocate();
        if ($alphaSet->isEmpty()) {
            // match empty string
            $this->epsilonTransitions->addTransition($this->startNode, $this->finalNode, true);
        } else {
            // match alpha set
            $this->alphaTransitions->addTransition($this->startNode, $this->finalNode, $alphaSet);
        }
    }

    /**
     * Get final state/node
     *
     * @return int
     */
    public function getFinalNode(): int {
        return $this->finalNode;
    }

    /**
     * Get Allocator
     *
     * @return NodeAllocator
     */
    public function getAllocator(): NodeAllocator {
        return $this->allocator;
    }

    /**
     * Get start state/node
     *
     * @return int
     */
    public function getStartNode(): int {
        return $this->startNode;
    }

    /**
     * Get Epsilon Transitions
     *
     * @return EpsilonTransitionList
     */
    public function getEpsilonTransitions(): EpsilonTransitionList {
        return $this->epsilonTransitions;
    }

    /**
     * Get transitions with AlphaSet
     *
     * @return AlphaTransitionList
     */
    public function getAlphaTransitions(): AlphaTransitionList {
        return $this->alphaTransitions;
    }

    /**
     * Representing the RE: RE?
     *
     * @return $this
     */
    public function NoneOrOne(): EpsilonNFA {
        $this->epsilonTransitions->addTransition($this->startNode, $this->finalNode);
        return $this;
    }

    /**
     * Representing the RE: RE*
     *
     * @return $this
     */
    public function MultipleTimes(): EpsilonNFA {
        $nStart = $this->allocator->allocate();
        $nFinal = $this->allocator->allocate();
        // add backward transition for the repetition of the current RE
        $this->epsilonTransitions->addTransition($this->finalNode, $this->startNode);
        // add forward transition for the <empty> match (new)
        $this->epsilonTransitions->addTransition($nStart, $nFinal);
        // connect the repetition with the empty match
        $this->epsilonTransitions->addTransition($nStart, $this->startNode);
        $this->epsilonTransitions->addTransition($this->finalNode, $nFinal);
        // set new start/end
        $this->startNode = $nStart;
        $this->finalNode = $nFinal;
        return $this;
    }

    /**
     * Representing the RE: RE+
     *
     * @return $this
     */
    public function AtLeastOne(): EpsilonNFA {
        $nStart = $this->allocator->allocate();
        $nFinal = $this->allocator->allocate();
        // add backward transition for the repetition of the current RE
        $this->epsilonTransitions->addTransition($this->finalNode, $this->startNode);
        // connect the repetition
        $this->epsilonTransitions->addTransition($nStart, $this->startNode);
        $this->epsilonTransitions->addTransition($this->finalNode, $nFinal);
        // set new start/end
        $this->startNode = $nStart;
        $this->finalNode = $nFinal;
        return $this;
    }

    /**
     * Representing the RE: RE1RE2
     *
     * @param EpsilonNFA $other
     * @return $this
     */
    public function Concat(EpsilonNFA $other): EpsilonNFA {
        $rebase = $this->copyAndRebase($other);
        // and connect them
        $this->epsilonTransitions->addTransition($this->finalNode, $other->startNode + $rebase);
        // set new end, start will be same
        $this->finalNode = $other->finalNode + $rebase;
        return $this;
    }

    /**
     * Representing the RE: RE1|RE2
     *
     * @param EpsilonNFA $other
     * @return $this
     */
    public function Choice(EpsilonNFA $other): EpsilonNFA {
        $rebase = $this->copyAndRebase($other);
        // and connect them
        $nStart = $this->allocator->allocate();
        $nFinal = $this->allocator->allocate();
        $this->epsilonTransitions->addTransition($nStart, $this->startNode);
        $this->epsilonTransitions->addTransition($nStart, $other->startNode + $rebase);
        $this->epsilonTransitions->addTransition($this->finalNode, $nFinal);
        $this->epsilonTransitions->addTransition($other->finalNode + $rebase, $nFinal);
        // set new start/end
        $this->startNode = $nStart;
        $this->finalNode = $nFinal;
        return $this;
    }

    /**
     * Copy the other eNFA into this "Nodespace" such that we can connect them
     *
     * @param EpsilonNFA $other
     * @return int
     */
    protected function copyAndRebase(EpsilonNFA $other): int {
        // rebase value for other nodes (simple mapping other nodes in this allocators space)
        $rebase = $this->allocator->getLast() + 1;
        // allocate nodes for the other ENFA nodes
        $this->allocator->allocate($other->allocator->allocations());
        $this->epsilonTransitions->copyFrom($other->epsilonTransitions, $rebase, true);
        $this->alphaTransitions->copyFrom($other->alphaTransitions, $rebase, true);
        return $rebase;
    }

    //TODO: move simulation into EpsilonNFARunner (or EpsilonNFASimulator)

    /**
     * check if given string matches this nfa
     *
     * @param string $match
     * @return bool
     */
    public function simulateMatch(string $match): bool {
        // start state with startnode
        $state = $this->getNodeSet();
        $state[$this->getStartNode()] = true;
        $this->epsilonNodeStep($state);
        // match
        for ($i = 0; $i < strlen($match); $i++) {
            $alpha = new AlphaSet();
            $alpha->set(ord($match[$i]));
            // transition with given alpha
            $state = $this->alphaNodeStep($state, $alpha);
            if ($this->checkNodeSetEmpty($state)) {
                // transition didn't end in any state, a clear mismatch
                return false;
            }
            // resolve epsilon transitions
            $this->epsilonNodeStep($state);
        }
        // only matching if NFA simulation ends with at least one final state in the state set
        return $this->checkNodeSetHasFinal($state);
    }

    /**
     * Allocate a node set for simulation
     *
     * @return SplFixedArray<?bool>
     */
    protected function getNodeSet(): SplFixedArray {
        // TODO: replace that lazyness with something good, like an actual bitmap
        return new SplFixedArray($this->getAllocator()->allocations());
    }

    /**
     * Resolve Epsilon transitions for the given source set
     *
     * @param SplFixedArray<?bool> $sourceNodes
     */
    protected function epsilonNodeStep(SplFixedArray &$sourceNodes): void {
        $modified = true;
        // doing fixpoint iteration until all epsilon transitions are resolved
        while ($modified) {
            $modified = false;
            /**
             * @var EpsilonTransition $epsilonTransition
             */
            foreach ($this->getEpsilonTransitions()->enumerator() as $epsilonTransition) {
                // add epsilon transition target node only if it's not part of the set
                if (($sourceNodes[$epsilonTransition->getFromNode()] === true) &&
                    ($sourceNodes[$epsilonTransition->getToNode()] !== true)) {
                    $modified = true;
                    $sourceNodes[$epsilonTransition->getToNode()] = true;
                }
            }
        }
    }

    /**
     * Calculate the node set (state) for any of the given input characters
     *
     * @param SplFixedArray<?bool> $sourceNodes
     * @param AlphaSet $alphaSet
     * @return SplFixedArray<?bool>
     */
    protected function alphaNodeStep(SplFixedArray $sourceNodes, AlphaSet $alphaSet): SplFixedArray {
        $destNodes = $this->getNodeSet();
        /**
         * @var AlphaTransition $alphaTransition
         */
        foreach ($this->getAlphaTransitions()->enumerator() as $alphaTransition) {
            /**
             * @var AlphaTransition $alphaTransition
             */
            if ($sourceNodes[$alphaTransition->getFromNode()] === true && !$alphaTransition->getAlphaSet()->isDisjoint($alphaSet)) {
                $destNodes[$alphaTransition->getToNode()] = true;
            }
        }
        return $destNodes;
    }

    /**
     * Check if source set is empty
     *
     * @param SplFixedArray<?bool> $sourceNodes
     * @return bool
     */
    protected function checkNodeSetEmpty(SplFixedArray $sourceNodes): bool {
        /**
         * @var int $key
         * @var bool|null $value
         */
        foreach ($sourceNodes as $key => $value) {
            if ($value === true) return false;
        }
        return true;
    }

    /**
     * Check if source set contains the final state
     *
     * @param SplFixedArray<?bool> $sourceNodes
     * @return bool
     */
    protected function checkNodeSetHasFinal(SplFixedArray $sourceNodes): bool {
        return $sourceNodes[$this->getFinalNode()] === true;
    }
}