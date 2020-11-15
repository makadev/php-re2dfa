<?php

namespace makadev\RE2DFA\FiniteAutomaton;

use makadev\RE2DFA\CharacterSet\AlphaSet;

class DFAFixedNodeTransition {

    /**
     *
     * @var AlphaSet
     */
    public AlphaSet $transitionSet;

    /**
     *
     * @var int
     */
    public int $targetNode;

    /**
     * DFAFixedNodeTransition constructor.
     *
     * @param AlphaSet $transitionSet
     * @param int $targetNode
     */
    public function __construct(AlphaSet $transitionSet, int $targetNode) {
        $this->transitionSet = $transitionSet;
        $this->targetNode = $targetNode;
    }
}