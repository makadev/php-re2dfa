<?php

namespace makadev\RE2DFA\FiniteAutomaton;

use SplDoublyLinkedList;
use SplFixedArray;

class DFAFixedNode {
    /**
     * Outgoing transitions
     *
     * @var SplDoublyLinkedList<DFAFixedNodeTransition>
     */
    public $transitions;

    /**
     * Set of possible final state names
     * @var SplFixedArray<string>|null
     */
    public ?SplFixedArray $finalStates = null;

    /**
     *
     * DFAFixedNode constructor.
     */
    public function __construct() {
        $this->transitions = new SplDoublyLinkedList();
    }
}