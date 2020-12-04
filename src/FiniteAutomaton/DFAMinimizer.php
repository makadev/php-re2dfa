<?php


namespace makadev\RE2DFA\FiniteAutomaton;


use IteratorIterator;
use makadev\RE2DFA\CharacterSet\AlphaSet;
use makadev\RE2DFA\CharacterSet\DisjointAlphaSets;
use makadev\RE2DFA\NodeSet\NodeSet;
use makadev\RE2DFA\NodeSet\NodeSetMapper;
use makadev\RE2DFA\StringSet\StringMapper;
use SplFixedArray;

class DFAMinimizer {

    /**
     * Source DFA
     *
     * @var DFA
     */
    protected DFA $dfa;

    /**
     * Final state to ID mapping
     *
     * @var StringMapper
     */
    protected StringMapper $finalStateLT;

    /**
     * Mapping multiple final states to an ID
     *
     * @var StringMapper
     */
    protected StringMapper $multiFinalStateLT;

    /**
     * Current node set partition
     *
     * @var NodeSetMapper
     */
    protected NodeSetMapper $nodeSetSet;

    /**
     * whether final states are seen as individual or not
     *
     * @var bool
     */
    protected bool $individualFinals;

    /**
     * DFAMinimizer constructor.
     *
     * @param DFA $dfa
     * @param bool $individualFinals whether final states are seen as individual or not, setting this to false can
     * reduce the amount of nodes further but merges named final states together which fall into the same partition,
     * creating wrong matches for sub nfa's matching a common subset of a given language like digit [0-9] and positive
     * [0-9]+ matching "10" as digit after minimization.
     */
    public function __construct(DFA $dfa, bool $individualFinals = true) {
        $this->dfa = $dfa;
        $this->finalStateLT = new StringMapper();
        $this->multiFinalStateLT = new StringMapper();
        $this->nodeSetSet = new NodeSetMapper();
        $this->individualFinals = $individualFinals;
    }

    /**
     * Minimize DFA and return either a new minimized DFA or
     *
     * @return DFA
     */
    public function minimize(): DFA {
        if($this->nodeSetSet->count() > 0) {
            // reinitialize
            $this->__construct($this->dfa, $this->individualFinals);
        }
        // initial partition of non final and (different) final states
        $this->getInitialPartitions();
        $nodeSetSet = $this->nodeSetSet;
        // partition of alphasets used in transitions
        $disjointAlphas = $this->getDisjointAlphaSets();

        // partition algorithm
        $done = false;
        while (!$done) {
            $done = true;
            foreach ($disjointAlphas->enumerator(false) as $alphaSet) {
                // use index access here, iterator is used somewhere else
                $setPointer = 0;
                while ($setPointer < $nodeSetSet->count()) {
                    $nodeSet = $nodeSetSet->getNodeSetFor($setPointer);
                    // the algorithm will refine a set into at least one set with at least one element
                    // so there should never appear an empty set
                    assert($nodeSet->count() > 0);
                    $nsom = $this->refineSet($nodeSet, $alphaSet);
                    assert(count($nsom) > 0);
                    // if refinement only returned one set, nothing changed (same partition)
                    // otherwise we need to add the new partitions and mark the change
                    if (count($nsom) > 1) {
                        $nodeSetSet->remove($setPointer--);
                        foreach (array_values($nsom) as $newSet) {
                            $nodeSetSet->add($newSet, false);
                        }
                        $done = false;
                    }
                    $setPointer++;
                }
            }
        }

        // there can't be more partitions than previous nodes
        assert($nodeSetSet->count() <= $this->dfa->getNodes()->count());

        // check, if we have as many partitions as the previous DFA has nodes in which case nothing changed
        if ($nodeSetSet->count() === $this->dfa->getNodes()->count()) {
            return $this->dfa;
        }

        // dfa reconstruction
        return $this->reconstructDFA();
    }

    /**
     * reconstruct the minimized DFA from the node partitions and final states
     *
     * @return DFA
     */
    protected function reconstructDFA(): DFA {
        $nodeSetSet = $this->nodeSetSet;
        // create an mapping for.. mapping old dfa nodes to new ones
        $nodeRemap = new SplFixedArray($this->dfa->getNodes()->count());
        for ($i = 0; $i < $nodeSetSet->count(); $i++) {
            $ns = $nodeSetSet->getNodeSetFor($i);
            foreach ($ns->enumerator() as $nodeID) {
                $nodeRemap[$nodeID] = $i;
            }
        }
        // create new DFA nodes
        $nodes = new SplFixedArray($nodeSetSet->count());
        for ($i = 0; $i < $nodeSetSet->count(); $i++) {
            $nodes[$i] = new DFAFixedNode();
        }
        // merge old transitions with same source/target into new transitions
        $oldNodes = $this->dfa->getNodes();
        $oldNodesIter = new IteratorIterator($oldNodes);
        /**
         * @var int $oldNodeKey
         */
        /**
         * @var DFAFixedNode $oldNode
         */
        foreach ($oldNodesIter as $oldNodeKey => $oldNode) {
            $oldTransIter = new IteratorIterator($oldNode->transitions);
            /**
             * @var DFAFixedNodeTransition $oldTrans
             */
            foreach ($oldTransIter as $oldTrans) {
                $newTarget = $nodeRemap[$oldTrans->targetNode];
                $newTransitions = $nodes[$nodeRemap[$oldNodeKey]]->transitions;
                $newTransIter = new IteratorIterator($newTransitions);
                $added = false;
                /**
                 * @var DFAFixedNodeTransition $newTrans
                 */
                foreach ($newTransIter as $newTrans) {
                    if ($newTrans->targetNode === $newTarget) {
                        if (!$newTrans->transitionSet->contains($oldTrans->transitionSet)) {
                            $newTrans->transitionSet = $newTrans->transitionSet->union($oldTrans->transitionSet);
                        }
                        $added = true;
                        break;
                    }
                }
                if (!$added) {
                    $newTransitions->push(new DFAFixedNodeTransition(clone $oldTrans->transitionSet, $newTarget));
                }
            }
        }
        // copy one the final states of any representative for each partition and assign it to the new node or
        // for non individual finals merge all final states of a partition
        $oldNodesIter = new IteratorIterator($oldNodes);
        /**
         * @var int $oldNodeKey
         */
        /**
         * @var DFAFixedNode $oldNode
         */
        foreach ($oldNodesIter as $oldNodeKey => $oldNode) {
            if ($oldNode->finalStates !== null) {
                $newNodeID = $nodeRemap[$oldNodeKey];
                if ($nodes[$newNodeID]->finalStates === null) {
                    $nodes[$newNodeID]->finalStates = clone $oldNode->finalStates;
                } else {
                    if (!$this->individualFinals) {
                        //TODO: optimize.. better replace the final states in DFAFixedNode with something
                        // that allows for better/optimized handling of final states and mappings
                        $finIter = new IteratorIterator($oldNode->finalStates);
                        /**
                         * @var string $cstate
                         */
                        foreach ($finIter as $cstate) {
                            /**
                             * @var SplFixedArray<string> $fs
                             */
                            $fs = $nodes[$newNodeID]->finalStates;
                            $fsIter = new IteratorIterator($fs);
                            $added = false;
                            foreach ($fsIter as $state) {
                                if ($state === $cstate) {
                                    $added = true;
                                    break;
                                }
                            }
                            if (!$added) {
                                $fs->setSize($fs->getSize() + 1);
                                $fs[$fs->getSize() - 1] = $cstate;
                            }
                        }
                    }
                }
            }
        }
        // create new DFA and don't forget remapping the startnode
        return new DFA($nodes, $nodeRemap[$this->dfa->getStartNode()]);
    }

    /**
     * With given source set of the partition and the given alpha set, refine the partition
     * by checking transitions into other partitions.
     *
     * @param NodeSet $ns
     * @param AlphaSet $as
     * @return NodeSet[]
     */
    protected function refineSet(NodeSet $ns, AlphaSet $as): array {
        $nsm = $this->nodeSetSet;
        /**
         * @var NodeSet[] $result
         */
        $result = [];
        $nodes = $this->dfa->getNodes();
        /**
         * @var int $nodeID
         */
        foreach ($ns->enumerator() as $nodeID) {
            $representative = -1;
            /**
             * @var DFAFixedNode $node
             */
            $node = $nodes[$nodeID];
            // refine partition $ns by sorting all of it's nodes into different partitions if
            // their target nodes are in different partitions
            $transIter = new IteratorIterator($node->transitions);
            /**
             * @var DFAFixedNodeTransition $t
             */
            foreach ($transIter as $t) {
                if ($t->transitionSet->contains($as)) {
                    $representative = $nsm->findRepresentativeSet($t->targetNode);
                    assert($representative !== null);
                    // since operating on DFA must only yield one transition to go for with given disjoint alpha subset
                    // we don't need to look further here
                    break;
                }
            }
            if (isset($result[$representative])) {
                $result[$representative]->add($nodeID);
            } else {
                $newSet = new NodeSet($nodes->count());
                $newSet->add($nodeID);
                $result[$representative] = $newSet;
            }
        }
        return $result;
    }

    /**
     * Return a unique ID for the multi final state, order of finalStates is not relevant
     * (f.e. ['int'] => 0, ['float'] => 1, ['int','float'] => 2, ['float,'int'] => 2)
     *
     * @param SplFixedArray $finalStates
     * @return int
     */
    protected function multiFinalStateUniqueID(SplFixedArray $finalStates): int {
        assert($finalStates !== null && $finalStates->count() > 0);
        /**
         * @var string[] $finalStatesArray
         */
        $finalStatesArray = $finalStates->toArray();
        // order is ignored -> sort such that ['int','float'], ['float,'int'] result in same id
        sort($finalStatesArray);
        $multiFinalStateString = "";
        foreach ($finalStatesArray as $value) {
            // this step also removes the need to check final state names for special characters, we simply replace
            // the names by a unique ID and use this to create a unique multi state id like "1:5:6:"
            $multiFinalStateString .= $this->finalStateLT->lookupAdd($value) . ':';
        }
        return $this->multiFinalStateLT->lookupAdd($multiFinalStateString);
    }

    /**
     * Create the initial partitions by partitioning the DFA Nodes into
     * non final and final sets. The final set partitions will be calculated based on the final states each node
     * can end in. This is not as optimal as partitioning into non finals and finals but it keeps the partitions from
     * collapsing into (named) final state combinations that didn't exist before.
     *
     * Example "digit" and "float" matching machines [0-9] and [0-9]*\.[0-9]+ would collapse into a state machine,
     * where the sub dfa matching [0-9]* or [0-9] returns the final state names "digit" and "float" which is
     * wrong for "digit".
     */
    protected function getInitialPartitions(): void {
        $nodes = $this->dfa->getNodes();
        /**
         * @var NodeSet[]
         */
        $FNodeSets = [];
        $NFNodes = new NodeSet($nodes->count());
        $nodeIter = new IteratorIterator($nodes);
        /**
         * @var int $nodeID
         */
        /**
         * @var DFAFixedNode $node
         */
        foreach ($nodeIter as $nodeID => $node) {
            if ($node->finalStates !== null && ($node->finalStates->count() > 0)) {
                $fsID = $this->individualFinals ? $this->multiFinalStateUniqueID($node->finalStates) : 0;
                if (isset($FNodeSets[$fsID])) {
                    $FNodeSets[$fsID]->add($nodeID);
                } else {
                    $finSet = new NodeSet($nodes->count());
                    $finSet->add($nodeID);
                    $FNodeSets[$fsID] = $finSet;
                }
            } else {
                $NFNodes->add($nodeID);
            }
        }

        // add all created sets to the initial partition
        $this->nodeSetSet->add($NFNodes, false);
        foreach (array_values($FNodeSets) as $set) {
            $this->nodeSetSet->add($set, false);
        }
    }

    /**
     * Calculate a set of alpha sets for all transitions in the DFA. This can then be used to check transitions for
     * whole character classes instead of each character (if possible).
     *
     * On the downside, this calculation might be more expensive for small machines.
     *
     * @return DisjointAlphaSets
     */
    protected function getDisjointAlphaSets(): DisjointAlphaSets {
        $nodes = $this->dfa->getNodes();
        $disjointAlphas = new DisjointAlphaSets();
        $nodeIter = new IteratorIterator($nodes);
        /**
         * @var DFAFixedNode $node
         */
        foreach ($nodeIter as $node) {
            $transIter = new IteratorIterator($node->transitions);
            /**
             * @var DFAFixedNodeTransition $transition
             */
            foreach ($transIter as $transition) {
                $disjointAlphas->addAlpha($transition->transitionSet);
            }
        }
        return $disjointAlphas;
    }
}