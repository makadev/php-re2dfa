<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\NodeSet\NodeAllocator;
use makadev\RE2DFA\NodeSet\NodeSet;
use makadev\RE2DFA\NodeSet\NodeSetMapper;

class DFABuilder {

    /**
     *
     * @var NodeAllocator
     */
    private NodeAllocator $allocator;

    /**
     *
     * @var AlphaTransitionList
     */
    private AlphaTransitionList $alphaTransitions;

    /**
     *
     * @var EpsilonTransitionList
     */
    private EpsilonTransitionList $epsilonTransitions;

    /**
     *
     * @var int
     */
    private int $startNode;

    /**
     *
     * @var DFABuilderFinalNode[] $finalNodes
     */
    private array $finalNodes;

    /**
     * DFABuilder constructor.
     */
    public function __construct() {
        $this->allocator = new NodeAllocator();
        $this->alphaTransitions = new AlphaTransitionList();
        $this->epsilonTransitions = new EpsilonTransitionList();
        $this->startNode = $this->allocator->allocate();
        $this->finalNodes = [];
    }

    /**
     * Copy the other eNFA into this "Nodespace" such that we can connect them
     *
     * @param EpsilonNFA $other
     * @return int
     */
    protected function copyAndRebase(EpsilonNFA $other): int {
        // first new node to be assigned
        $rebase = $this->allocator->getLast() + 1;
        // allocate nodes for the other ENFA nodes
        $this->allocator->allocate($other->getAllocator()->allocations());
        $this->epsilonTransitions->copyFrom($other->getEpsilonTransitions(), $rebase, true);
        $this->alphaTransitions->copyFrom($other->getAlphaTransitions(), $rebase, true);
        return $rebase;
    }

    /**
     * add another EpsilonNFA with a final state to be transformed into the dfa
     *
     * @param EpsilonNFA $enfa
     * @param string $finalStateName
     * @return $this
     */
    public function addENFA(EpsilonNFA $enfa, string $finalStateName): DFABuilder {
        $rebase = $this->copyAndRebase($enfa);
        // record the final state
        $connectedFinal = false;
        foreach ($this->finalNodes as $node) {
            if ($node->getFinalName() === $finalStateName) {
                // all final states for NFAs with the same final name will be bound to an explicit "connector" node
                // -> check if we need either create one or connect
                if ($node->getConnected()) {
                    // connect final state with the connector node
                    $this->epsilonTransitions->addTransition($enfa->getFinalNode() + $rebase, $node->getNode());
                } else {
                    // create new connector node and connect
                    $newFinal = $this->allocator->allocate();
                    // connect old NFA who had this final state and update the node
                    $this->epsilonTransitions->addTransition($node->getNode(), $newFinal);
                    $node->setConnected($newFinal);
                    // connect the new one
                    $this->epsilonTransitions->addTransition($enfa->getFinalNode() + $rebase, $newFinal);
                }
                $connectedFinal = true;
                break;
            }
        }
        if (!$connectedFinal) {
            $this->finalNodes[] = new DFABuilderFinalNode($enfa->getFinalNode() + $rebase, $finalStateName);
        }
        // and connect the start with the NFA start
        $this->epsilonTransitions->addTransition($this->startNode, $enfa->getStartNode() + $rebase);

        return $this;
    }

    /**
     * build a dfa
     *
     * @return DFA
     */
    public function build(): DFA {
        $nodeSetMapper = new NodeSetMapper();
        // calculate the start set
        $startSet = new NodeSet($this->allocator->allocations());
        $startSet->add($this->startNode);
        $startSet->addReachableWithEpsilon($this->epsilonTransitions);
        // set the start node for the dfa
        $dfaStartNode = $nodeSetMapper->add($startSet, false);
        assert($dfaStartNode === 0);
        //
        $dfaTransitions = new AlphaTransitionList();
        //
        $finalStateTable = [];
        //
        $currentSource = $dfaStartNode;
        do {
            $fromSet = $nodeSetMapper->getNodeSetFor($currentSource);
            // add node to final states when nodeSet contains final states
            foreach ($this->finalNodes as $node) {
                /**
                 * @var DFABuilderFinalNode $node
                 */
                if ($fromSet->isIn($node->getNode())) {
                    if (!isset($finalStateTable[$node->getFinalName()])) {
                        $finalStateTable[$node->getFinalName()] = [$currentSource];
                    } else {
                        $arr = $finalStateTable[$node->getFinalName()];
                        $found = false;
                        foreach ($arr as $value) {
                            if ($value === $currentSource) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $finalStateTable[$node->getFinalName()][] = $currentSource;
                        }
                    }
                }
            }
            // calculate disjoint AlphaSets (optimized sets)
            $alphas = $this->alphaTransitions->getDisjointAlphas($fromSet);
            // create new nodeSet and transitions for each Disjoint AlphaSet transition
            for ($alphas->rewind(); $alphas->valid(); $alphas->next()) {
                /**
                 * @var AlphaSet $alphaSet
                 */
                $alphaSet = $alphas->current();
                $toNodeSet = new NodeSet($this->allocator->allocations());
                $toNodeSet->addReachableWithAlpha($fromSet, $this->alphaTransitions, $alphaSet);
                $toNodeSet->addReachableWithEpsilon($this->epsilonTransitions);
                $toNewNode = $nodeSetMapper->add($toNodeSet);
                $dfaTransitions->addTransition($currentSource, $toNewNode, $alphaSet);
            }
        } while (++$currentSource < $nodeSetMapper->getAllocator()->allocations());

        $result = new DFA($nodeSetMapper->getAllocator(), $dfaStartNode, $finalStateTable, $dfaTransitions);
        return $result;
    }
}