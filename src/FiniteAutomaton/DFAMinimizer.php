<?php


namespace makadev\RE2DFA\FiniteAutomaton;


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
     * DFAMinimizer constructor.
     *
     * @param DFA $dfa
     */
    public function __construct(DFA $dfa) {
        $this->dfa = $dfa;
        $this->finalStateLT = new StringMapper();
        $this->multiFinalStateLT = new StringMapper();
        $this->nodeSetSet = new NodeSetMapper();
    }

    /**
     * Minimize DFA and return either a new minimized DFA or
     *
     * @return DFA
     */
    public function minimize(): DFA {
        $this->__construct($this->dfa);
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

        // check, if we have as many partitions as the previous DFA, nothing realy changes
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
        $oldNodes = $this->dfa->getNodes();
        $nodes = new SplFixedArray($nodeSetSet->count());
        for ($i = 0; $i < $nodeSetSet->count(); $i++) {
            $nodes[$i] = new DFAFixedNode();
        }
        // merge old transitions with same source/target into new transitions
        for ($oldNodes->rewind(); $oldNodes->valid(); $oldNodes->next()) {
            /**
             * @var DFAFixedNode $oldNode
             */
            $oldNode = $oldNodes->current();
            for ($oldNode->transitions->rewind(); $oldNode->transitions->valid(); $oldNode->transitions->next()) {
                /**
                 * @var DFAFixedNodeTransition $oldTrans
                 */
                $oldTrans = $oldNode->transitions->current();
                $newTarget = $nodeRemap[$oldTrans->targetNode];
                $newTransitions = $nodes[$nodeRemap[$oldNodes->key()]]->transitions;
                $added = false;
                for ($newTransitions->rewind(); $newTransitions->valid(); $newTransitions->next()) {
                    /**
                     * @var DFAFixedNodeTransition $newTrans
                     */
                    $newTrans = $newTransitions->current();
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
        // copy one the final states of any representative for each partition and assign it to the new node
        for ($oldNodes->rewind(); $oldNodes->valid(); $oldNodes->next()) {
            /**
             * @var DFAFixedNode $oldNode
             */
            $oldNode = $oldNodes->current();
            if ($oldNode->finalStates !== null) {
                $newNodeID = $nodeRemap[$oldNodes->key()];
                if ($nodes[$newNodeID]->finalStates === null) {
                    $nodes[$newNodeID]->finalStates = clone $oldNode->finalStates;
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
            for ($node->transitions->rewind(); $node->transitions->valid(); $node->transitions->next()) {
                /**
                 * @var DFAFixedNodeTransition $t
                 */
                $t = $node->transitions->current();
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
        for ($nodes->rewind(); $nodes->valid(); $nodes->next()) {
            /**
             * @var DFAFixedNode $node
             */
            $node = $nodes->current();
            $nodeID = $nodes->key();
            if ($node->finalStates !== null && ($node->finalStates->count() > 0)) {
                $fsID = $this->multiFinalStateUniqueID($node->finalStates);
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
        for ($nodes->rewind(); $nodes->valid(); $nodes->next()) {
            /**
             * @var DFAFixedNode $node
             */
            $node = $nodes->current();
            for ($node->transitions->rewind(); $node->transitions->valid(); $node->transitions->next()) {
                /**
                 * @var DFAFixedNodeTransition $transition
                 */
                $transition = $node->transitions->current();
                $disjointAlphas->addAlpha($transition->transitionSet);
            }
        }
        return $disjointAlphas;
    }
}